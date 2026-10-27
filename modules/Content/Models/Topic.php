<?php

declare(strict_types=1);

namespace Modules\Content\Models;

use Catch\Base\CatchModel as Model;
use Catch\Exceptions\FailedException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Content\Enums\ArticleStatus;
use Modules\Content\Enums\TopicStatus;
use Modules\Content\Jobs\PlanTopics;
use Modules\Content\Jobs\WriteArticle;
use Modules\Site\Models\GameProfile;
use Modules\Site\Models\Site;

/**
 * @property int $id
 * @property int $site_id
 * @property string $title
 * @property string $keyword
 * @property string $intent
 * @property int $status
 * @property Site|null $site
 */
class Topic extends Model
{
    protected $table = 'content_topics';

    protected $fillable = ['id', 'site_id', 'title', 'keyword', 'intent', 'status', 'source', 'creator_id', 'created_at', 'updated_at', 'deleted_at'];

    protected array $fields = ['id', 'site_id', 'title', 'keyword', 'intent', 'status', 'source', 'created_at'];

    protected array $form = ['site_id', 'title', 'keyword', 'intent', 'status'];

    public array $searchable = [
        'site_id' => '=',
        'title' => 'like',
        'status' => '=',
    ];

    protected $casts = [
        'site_id' => 'integer',
        'status' => 'integer',
        'source' => 'integer',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    /**
     * 让 AI 为站点生成一批选题
     */
    public function generate(int $siteId, int $count): bool
    {
        static::ensureProfileConfirmed($siteId);
        PlanTopics::dispatch($siteId, max(1, min(20, $count)));

        return true;
    }

    /**
     * 用选题创建一篇文章并进入生成流水线
     */
    public static function writeArticle(int $id): int
    {
        $topic = static::query()->findOrFail($id);
        if ($topic->status !== TopicStatus::Pending->value) {
            throw new FailedException('只有待写的选题可以生成文章');
        }
        static::ensureProfileConfirmed($topic->site_id);

        $article = Article::query()->create([
            'site_id' => $topic->site_id,
            'topic_id' => $topic->id,
            'title' => $topic->title,
            'keyword' => $topic->keyword,
            'status' => ArticleStatus::Writing->value,
            'source' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        $topic->update(['status' => TopicStatus::Used->value]);

        WriteArticle::dispatch($article->id);

        return $article->id;
    }

    public static function ensureProfileConfirmed(int $siteId): void
    {
        if (! GameProfile::query()->where('site_id', $siteId)->first()?->isConfirmed()) {
            throw new FailedException('请先确认该站点的游戏资料');
        }
    }
}
