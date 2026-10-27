<?php

declare(strict_types=1);

namespace Modules\Ad\Models;

use Catch\Base\CatchModel as Model;
use Catch\Enums\Status;
use Illuminate\Support\Facades\Cache;
use Modules\Front\Support\PageCache;

/**
 * @property int $id
 * @property string $name
 * @property string $position
 * @property int $type
 * @property string $image
 * @property int $width
 * @property int $height
 * @property string $link
 * @property string $alt
 * @property string|null $html
 * @property array|null $site_ids
 * @property int $weight
 * @property int $start_at
 * @property int $end_at
 * @property int $clicks
 * @property int $status
 */
class Ad extends Model
{
    protected $table = 'ads';

    protected $fillable = [
        'id', 'name', 'position', 'type', 'image', 'width', 'height', 'link', 'alt', 'html', 'site_ids', 'weight',
        'start_at', 'end_at', 'clicks', 'status', 'creator_id', 'created_at', 'updated_at', 'deleted_at',
    ];

    protected array $fields = [
        'id', 'name', 'position', 'type', 'image', 'link', 'site_ids', 'weight', 'start_at', 'end_at', 'clicks', 'status', 'created_at',
    ];

    protected array $form = ['name', 'position', 'type', 'image', 'width', 'height', 'link', 'alt', 'html', 'site_ids', 'weight', 'start_at', 'end_at', 'status'];

    public array $searchable = [
        'name' => 'like',
        'position' => '=',
        'status' => '=',
    ];

    protected $casts = [
        'type' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'site_ids' => 'array',
        'weight' => 'integer',
        'start_at' => 'integer',
        'end_at' => 'integer',
        'clicks' => 'integer',
        'status' => 'integer',
    ];

    protected const CACHE_KEY = 'ad:active';

    protected static function booted(): void
    {
        // 广告变化后清掉前台页面缓存，新广告才能显示
        $refresh = function () {
            Cache::forget(self::CACHE_KEY);
            PageCache::flushAll();
        };
        static::saved($refresh);
        static::deleted($refresh);
    }

    /**
     * 所有启用且在投放期内的广告，按广告位分组，缓存 5 分钟
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function active(): array
    {
        $ads = Cache::remember(self::CACHE_KEY, 300, fn () => static::query()
            ->where('status', Status::Enable->value)
            ->where(fn ($query) => $query->where('end_at', 0)->orWhere('end_at', '>', time()))
            ->get(['id', 'position', 'type', 'image', 'width', 'height', 'link', 'alt', 'html', 'site_ids', 'weight', 'start_at', 'end_at'])
            ->map(fn (Ad $ad) => ['site_ids' => $ad->site_ids ?? []] + $ad->getAttributes())
            ->all());

        $now = time();

        return collect($ads)
            ->filter(fn ($ad) => $ad['start_at'] <= $now && (! $ad['end_at'] || $ad['end_at'] > $now))
            ->groupBy('position')
            ->map(fn ($items) => $items->values()->all())
            ->all();
    }

    /**
     * 记录一次点击，返回跳转地址
     */
    public static function click(int $id): ?string
    {
        $ad = static::query()->where('status', Status::Enable->value)->find($id, ['id', 'link']);
        if (! $ad?->link) {
            return null;
        }

        static::query()->whereKey($id)->increment('clicks');

        return $ad->link;
    }
}
