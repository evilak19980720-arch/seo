<?php

declare(strict_types=1);

namespace Modules\Content\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Modules\Ai\Enums\PromptType;
use Modules\Ai\Support\Ai;
use Modules\Content\Enums\ArticleStatus;
use Modules\Content\Models\Article;
use Modules\Content\Models\Setting;
use Modules\Content\Support\Text;
use Modules\Site\Models\GameProfile;
use Throwable;

/**
 * 文章流水线：生成 → 润色 → SEO 信息 → 本地检查（字数、相似度）→ AI 审核
 * 审核不通过时带着问题重写，超过次数进入「审核未通过」，不会自动发布
 */
class WriteArticle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 1200;

    /** 需要全部达到及格分的审核项 */
    public const CHECKS = ['factual', 'useful', 'original', 'language', 'honest'];

    public function __construct(
        public readonly int $articleId
    ) {
    }

    public function backoff(): array
    {
        return [120];
    }

    public function handle(Ai $ai): void
    {
        $article = Article::query()->with(['site', 'topic'])->find($this->articleId);
        if (! $article || $article->status !== ArticleStatus::Writing->value || ! $article->site) {
            return;
        }

        $site = $article->site;
        $profile = GameProfile::query()->where('site_id', $site->id)->first();
        if (! $profile?->isConfirmed()) {
            $this->fail(new \RuntimeException('游戏资料未确认，不能生成文章'));

            return;
        }

        $settings = Setting::values();
        $base = [
            'site_name' => $site->name,
            'locale' => $site->locale,
            'game_profile' => json_encode($profile->facts(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ];
        $topic = $article->topic?->title ?: $article->title;
        $keyword = $article->keyword ?: (string) $article->topic?->keyword;
        $existingTitles = PlanTopics::existingTitles($site->id);
        $existingTitles = array_values(array_diff($existingTitles, [$topic, $article->title]));

        $issues = [];
        $attempts = $article->attempts;
        $result = null;

        while ($attempts < $settings['max_attempts']) {
            $attempts++;

            $brief = $topic;
            if ($issues) {
                $brief .= "\n\nA previous draft was rejected. Fix these problems:\n- ".implode("\n- ", $issues);
            }

            $draft = $ai->run(PromptType::ArticleGenerate, $base + [
                'topic' => $brief,
                'keyword' => $keyword,
                'min_words' => (string) $settings['min_words'],
                'max_words' => (string) $settings['max_words'],
            ], $site->id)->text;

            $markdown = $ai->run(PromptType::ArticlePolish, $base + ['article' => $draft], $site->id)->text;
            $markdown = static::stripFence($markdown);

            $localIssues = $this->localIssues($article, $markdown, $settings);
            $review = $ai->run(PromptType::ArticleReview, $base + [
                'article' => $markdown,
                'existing_titles' => $existingTitles ? '- '.implode("\n- ", $existingTitles) : '(none)',
            ], $site->id)->json();

            $scores = [];
            foreach (self::CHECKS as $check) {
                $scores[$check] = max(0, min(100, (int) ($review[$check] ?? 0)));
            }
            $issues = array_merge($localIssues, array_values(array_filter(array_map('strval', (array) ($review['issues'] ?? [])))));
            $minScore = min($scores);
            // 本地检查有问题时，无论 AI 打多少分都不通过
            $passed = $minScore >= $settings['review_pass_score'] && $localIssues === [];

            $result = compact('markdown', 'scores', 'issues', 'minScore', 'passed');

            $article->update(['attempts' => $attempts]);

            if ($passed) {
                break;
            }
        }

        if (! $result) {
            $article->update(['status' => ArticleStatus::Rejected->value, 'error' => '已达到最多生成次数']);

            return;
        }

        $markdown = $result['markdown'];
        $title = Text::title($markdown) ?: $topic;
        $seo = $this->seo($ai, $base, $markdown, $site->id);

        $article->update([
            'title' => Str::limit($title, 255, ''),
            'keyword' => Str::limit($keyword, 191, ''),
            'slug' => Article::uniqueSlug($site->id, ($seo['slug'] ?? '') ?: $title, $article->id),
            'meta_title' => Str::limit((string) ($seo['meta_title'] ?? $title), 255, ''),
            'meta_description' => Str::limit((string) ($seo['meta_description'] ?? ''), 500, ''),
            'cover' => $article->cover ?: (string) (($profile->screenshots ?? [])[0] ?? ''),
            'content' => Text::withoutTitle($markdown),
            'word_count' => Text::wordCount($markdown),
            'review_score' => $result['minScore'],
            'review_detail' => ['scores' => $result['scores'], 'issues' => $result['issues']],
            'status' => $result['passed'] ? ArticleStatus::Queued->value : ArticleStatus::Rejected->value,
            'error' => $result['passed'] ? '' : 'AI 审核未通过',
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Article::query()->whereKey($this->articleId)->update([
            'status' => ArticleStatus::Failed->value,
            'error' => mb_substr($exception?->getMessage() ?: '生成失败', 0, 1000),
        ]);
    }

    /**
     * @return array<int, string>
     */
    protected function localIssues(Article $article, string $markdown, array $settings): array
    {
        $issues = [];
        $words = Text::wordCount($markdown);
        if ($words < $settings['min_words']) {
            $issues[] = "The article has {$words} words; it must have at least {$settings['min_words']}.";
        }
        if ($words > $settings['max_words'] * 1.2) {
            $issues[] = "The article has {$words} words; keep it under {$settings['max_words']}.";
        }
        if (Text::title($markdown) === '') {
            $issues[] = 'The first line must be a "# " title.';
        }
        if (($similar = $this->mostSimilar($article, $markdown)) && $similar['score'] > $settings['similarity_threshold']) {
            $issues[] = "The article repeats too much of the existing article \"{$similar['title']}\". Cover a different angle.";
        }

        return $issues;
    }

    /**
     * @return array{title: string, score: float}|null
     */
    protected function mostSimilar(Article $article, string $markdown): ?array
    {
        $best = null;
        Article::query()
            ->where('site_id', $article->site_id)
            ->whereKeyNot($article->id)
            ->whereIn('status', [ArticleStatus::Queued->value, ArticleStatus::Published->value])
            ->select(['id', 'title', 'content'])
            ->orderByDesc('id')
            ->limit(300)
            ->each(function (Article $other) use ($markdown, &$best) {
                $score = Text::similarity($markdown, (string) $other->content);
                if (! $best || $score > $best['score']) {
                    $best = ['title' => $other->title, 'score' => $score];
                }
            });

        return $best;
    }

    protected function seo(Ai $ai, array $base, string $markdown, int $siteId): array
    {
        try {
            return $ai->run(PromptType::Seo, $base + ['article' => $markdown], $siteId)->json();
        } catch (Throwable) {
            // SEO 信息生成失败不影响文章，标题和描述可以人工补
            return [];
        }
    }

    protected static function stripFence(string $text): string
    {
        $text = trim($text);
        if (preg_match('/^```(?:markdown|md)?\s*(.+?)\s*```$/s', $text, $matches)) {
            return $matches[1];
        }

        return $text;
    }
}
