<?php

declare(strict_types=1);

namespace Modules\Site\Models;

use Catch\Base\CatchModel as Model;
use Catch\Exceptions\FailedException;
use Catch\Facade\Admin;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Site\Enums\ProfileStatus;
use Modules\Site\Jobs\GenerateGameProfile;

/**
 * 游戏资料：站点所有内容的事实依据。AI 先生成草稿，人工修改并确认后站点才能生成文章
 *
 * @property int $id
 * @property int $site_id
 * @property string $genre
 * @property string|null $summary
 * @property string|null $gameplay
 * @property array|null $features
 * @property array|null $faq
 * @property array|null $store_links
 * @property array|null $screenshots
 * @property string $video_url
 * @property int $status
 * @property Site|null $site
 */
class GameProfile extends Model
{
    protected $table = 'site_game_profiles';

    protected $fillable = [
        'id', 'site_id', 'genre', 'summary', 'gameplay', 'features', 'faq', 'store_links', 'screenshots', 'video_url',
        'status', 'error', 'confirmed_at', 'confirmed_by', 'creator_id', 'created_at', 'updated_at', 'deleted_at',
    ];

    protected array $form = ['genre', 'summary', 'gameplay', 'features', 'faq', 'store_links', 'screenshots', 'video_url'];

    protected $casts = [
        'site_id' => 'integer',
        'features' => 'array',
        'faq' => 'array',
        'store_links' => 'array',
        'screenshots' => 'array',
        'status' => 'integer',
        'confirmed_at' => 'integer',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function isConfirmed(): bool
    {
        return $this->status === ProfileStatus::Confirmed->value;
    }

    public static function forSite(int $siteId): self
    {
        return static::query()->firstOrCreate(
            ['site_id' => $siteId],
            ['status' => ProfileStatus::Generating->value, 'created_at' => time(), 'updated_at' => time()]
        );
    }

    /**
     * 提交给 AI 的资料（只包含事实字段）
     *
     * @return array<string, mixed>
     */
    public function facts(): array
    {
        return array_filter([
            'name' => $this->site?->name,
            'genre' => $this->genre,
            'summary' => $this->summary,
            'gameplay' => $this->gameplay,
            'features' => $this->features,
            'faq' => $this->faq,
            'store_links' => array_filter($this->store_links ?? []),
        ]);
    }

    public function regenerate(int $siteId): bool
    {
        $profile = static::forSite($siteId);
        if ($profile->isConfirmed()) {
            throw new FailedException('资料已确认，如需重新生成请先撤销确认');
        }

        $profile->update(['status' => ProfileStatus::Generating->value, 'error' => '']);
        GenerateGameProfile::dispatch($siteId);

        return true;
    }

    /**
     * 保存修改。已确认的资料修改后仍保持已确认
     */
    public function saveForSite(int $siteId, array $data): bool
    {
        $profile = static::forSite($siteId);

        return $profile->fill($this->filterData($data, true))->save();
    }

    public function confirm(int $siteId): bool
    {
        $profile = static::forSite($siteId);

        if (trim((string) $profile->summary) === '' || trim((string) $profile->gameplay) === '') {
            throw new FailedException('请先填写游戏简介和玩法');
        }
        if (count(array_filter($profile->screenshots ?? [])) < 1) {
            throw new FailedException('请至少上传 1 张真实的游戏截图');
        }

        return $profile->update([
            'status' => ProfileStatus::Confirmed->value,
            'confirmed_at' => time(),
            'confirmed_by' => (int) Admin::id(),
        ]);
    }

    public function unconfirm(int $siteId): bool
    {
        return static::forSite($siteId)->update(['status' => ProfileStatus::Draft->value, 'confirmed_at' => 0, 'confirmed_by' => 0]);
    }
}
