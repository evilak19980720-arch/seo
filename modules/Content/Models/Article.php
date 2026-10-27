<?php

declare(strict_types=1);

namespace Modules\Content\Models;

use Catch\Base\CatchModel as Model;
use Catch\Exceptions\FailedException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Content\Enums\ArticleStatus;
use Modules\Content\Events\ArticlePublished;
use Modules\Content\Events\ArticleUnpublished;
use Modules\Content\Jobs\WriteArticle;
use Modules\Content\Support\Text;
use Modules\Site\Models\Site;

/**
 * @property int $id
 * @property int $site_id
 * @property int $topic_id
 * @property string $title
 * @property string $slug
 * @property string $keyword
 * @property string $meta_title
 * @property string $meta_description
 * @property string $cover
 * @property string|null $content
 * @property int $word_count
 * @property int $status
 * @property int $review_score
 * @property array|null $review_detail
 * @property int $attempts
 * @property int $published_at
 * @property int $updated_at
 * @property Site|null $site
 * @property Topic|null $topic
 */
class Article extends Model
{
    protected $table = 'content_articles';

    protected $fillable = [
        'id', 'site_id', 'topic_id', 'title', 'slug', 'keyword', 'meta_title', 'meta_description', 'cover', 'content', 'word_count',
        'status', 'review_score', 'review_detail', 'attempts', 'error', 'source', 'published_at',
        'creator_id', 'created_at', 'updated_at', 'deleted_at',
    ];

    protected array $fields = [
        'id', 'site_id', 'topic_id', 'title', 'slug', 'keyword', 'word_count', 'status', 'review_score', 'attempts', 'error', 'source', 'published_at', 'created_at', 'updated_at',
    ];

    protected array $form = ['site_id', 'title', 'slug', 'keyword', 'meta_title', 'meta_description', 'cover', 'content'];

    public array $searchable = [
        'site_id' => '=',
        'title' => 'like',
        'status' => '=',
        'source' => '=',
    ];

    protected $casts = [
        'site_id' => 'integer',
        'topic_id' => 'integer',
        'word_count' => 'integer',
        'status' => 'integer',
        'review_score' => 'integer',
        'review_detail' => 'array',
        'attempts' => 'integer',
        'source' => 'integer',
        'published_at' => 'integer',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    /**
     * 人工新建或修改文章：自动计算字数，补全 slug
     */
    public function saveArticle(?int $id, array $data): mixed
    {
        if (isset($data['content'])) {
            $data['word_count'] = Text::wordCount($data['content']);
        }

        $siteId = (int) ($data['site_id'] ?? static::query()->whereKey($id)->value('site_id'));
        $data['slug'] = static::uniqueSlug($siteId, $data['slug'] ?? '' ?: ($data['title'] ?? ''), $id);

        if ($id) {
            unset($data['site_id']);
            $updated = $this->updateBy($id, $data);
            $article = static::query()->find($id);
            if ($article?->status === ArticleStatus::Published->value) {
                ArticlePublished::dispatch($article);
            }

            return $updated;
        }

        return $this->storeBy($data + ['status' => ArticleStatus::Queued->value, 'source' => 2]);
    }

    public static function uniqueSlug(int $siteId, string $value, ?int $ignoreId = null): string
    {
        $base = Text::slug($value) ?: 'artigo';
        $slug = $base;
        $i = 2;
        while (static::query()->where('site_id', $siteId)->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function publish(int $id): bool
    {
        $article = static::query()->findOrFail($id);
        if (! in_array($article->status, [ArticleStatus::Queued->value, ArticleStatus::Offline->value, ArticleStatus::Rejected->value], true)) {
            throw new FailedException('当前状态不能发布');
        }
        if (trim((string) $article->content) === '') {
            throw new FailedException('正文为空，不能发布');
        }

        $article->update(['status' => ArticleStatus::Published->value, 'published_at' => $article->published_at ?: time(), 'error' => '']);
        ArticlePublished::dispatch($article);

        return true;
    }

    public function unpublish(int $id): bool
    {
        $article = static::query()->findOrFail($id);
        $article->update(['status' => ArticleStatus::Offline->value]);
        ArticleUnpublished::dispatch($article);

        return true;
    }

    public function deleteArticle(int $id): mixed
    {
        $article = static::query()->findOrFail($id);
        $wasPublished = $article->status === ArticleStatus::Published->value;
        $deleted = $this->deleteBy($id);

        if ($wasPublished) {
            ArticleUnpublished::dispatch($article);
        }

        return $deleted;
    }

    /**
     * 重新走一遍 AI 生成流水线（覆盖当前正文）
     */
    public function rewrite(int $id): bool
    {
        $article = static::query()->findOrFail($id);
        if (in_array($article->status, [ArticleStatus::Writing->value, ArticleStatus::Published->value], true)) {
            throw new FailedException('生成中或已发布的文章不能重写，已发布的请先下线');
        }
        Topic::ensureProfileConfirmed($article->site_id);

        $article->update(['status' => ArticleStatus::Writing->value, 'attempts' => 0, 'error' => '']);
        WriteArticle::dispatch($article->id);

        return true;
    }
}
