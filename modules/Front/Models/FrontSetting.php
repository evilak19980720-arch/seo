<?php

declare(strict_types=1);

namespace Modules\Front\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Modules\Front\Support\PageCache;

/**
 * 所有站点共用的前台配置
 */
class FrontSetting extends Model
{
    protected $table = 'front_settings';

    protected $fillable = ['key', 'value', 'created_at', 'updated_at'];

    public $timestamps = false;

    protected const CACHE_KEY = 'front:settings';

    /**
     * @return array<string, array{default: string|bool, label: string, type: string, help?: string}>
     */
    public static function schema(): array
    {
        return [
            'head_code' => ['default' => '', 'type' => 'code', 'label' => '统计代码（head）', 'help' => '输出到所有站点的 </head> 前，例如 Google Analytics 4 代码；用同一个 GA4 媒体资源时按主机名区分站点'],
            'body_code' => ['default' => '', 'type' => 'code', 'label' => '页面底部代码', 'help' => '输出到所有站点的 </body> 前'],
            'footer_text' => ['default' => '', 'type' => 'text', 'label' => '页脚附加文字', 'help' => '例如公司名称、联系方式，{site} 会替换为网站名称'],
            'indexnow_enabled' => ['default' => false, 'type' => 'bool', 'label' => '发布后推送 IndexNow', 'help' => '文章发布、下线后通知 Bing 等支持 IndexNow 的搜索引擎（Google 不支持）'],
        ];
    }

    /**
     * @return array<string, string|bool>
     */
    public static function values(): array
    {
        return Cache::remember(self::CACHE_KEY, 600, function () {
            $saved = static::query()->pluck('value', 'key')->all();
            $values = [];
            foreach (static::schema() as $key => $item) {
                $value = $saved[$key] ?? $item['default'];
                $values[$key] = is_bool($item['default']) ? filter_var($value, FILTER_VALIDATE_BOOL) : (string) $value;
            }

            return $values;
        });
    }

    public static function get(string $key): string|bool
    {
        return static::values()[$key] ?? static::schema()[$key]['default'];
    }

    public static function saveAll(array $values): bool
    {
        foreach (static::schema() as $key => $item) {
            if (! array_key_exists($key, $values)) {
                continue;
            }
            $value = is_bool($item['default']) ? (filter_var($values[$key], FILTER_VALIDATE_BOOL) ? '1' : '0') : (string) $values[$key];
            static::query()->updateOrCreate(['key' => $key], ['value' => $value, 'created_at' => time(), 'updated_at' => time()]);
        }

        Cache::forget(self::CACHE_KEY);
        PageCache::flushAll();

        return true;
    }

    /**
     * IndexNow 验证用的 key，第一次使用时生成
     */
    public static function indexNowKey(): string
    {
        $key = static::query()->where('key', 'indexnow_key')->value('value');
        if (! $key) {
            $key = bin2hex(random_bytes(16));
            static::query()->updateOrCreate(['key' => 'indexnow_key'], ['value' => $key, 'created_at' => time(), 'updated_at' => time()]);
        }

        return $key;
    }
}
