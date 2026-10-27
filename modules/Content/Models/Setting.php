<?php

declare(strict_types=1);

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * 内容生产配置（键值对），未设置时使用默认值
 */
class Setting extends Model
{
    protected $table = 'content_settings';

    protected $fillable = ['key', 'value', 'created_at', 'updated_at'];

    public $timestamps = false;

    protected const CACHE_KEY = 'content:settings';

    /**
     * 默认值与说明
     *
     * @return array<string, array{default: int|float|bool, label: string, min?: int|float, max?: int|float}>
     */
    public static function schema(): array
    {
        return [
            'auto_enabled' => ['default' => false, 'label' => '自动生产（定时为每个站生成和发布文章）'],
            'weekly_articles_per_site' => ['default' => 2, 'label' => '每站每周最多生成文章数', 'min' => 0, 'max' => 50],
            'daily_publish_per_site' => ['default' => 1, 'label' => '每站每天最多发布文章数', 'min' => 0, 'max' => 20],
            'topic_backlog' => ['default' => 5, 'label' => '每站保持的待写选题数', 'min' => 1, 'max' => 50],
            'min_words' => ['default' => 700, 'label' => '文章最少字数', 'min' => 200, 'max' => 5000],
            'max_words' => ['default' => 1400, 'label' => '文章最多字数', 'min' => 300, 'max' => 8000],
            'review_pass_score' => ['default' => 80, 'label' => 'AI 审核及格分（每一项都需达到）', 'min' => 50, 'max' => 100],
            'max_attempts' => ['default' => 3, 'label' => '单篇最多生成次数（含退回重写）', 'min' => 1, 'max' => 5],
            'similarity_threshold' => ['default' => 0.5, 'label' => '与本站已有文章的相似度上限（0–1）', 'min' => 0.1, 'max' => 1],
        ];
    }

    /**
     * @return array<string, int|float|bool>
     */
    public static function values(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            $saved = static::query()->pluck('value', 'key')->all();
            $values = [];
            foreach (static::schema() as $key => $item) {
                $values[$key] = static::cast($saved[$key] ?? $item['default'], $item['default']);
            }

            return $values;
        });
    }

    public static function get(string $key): int|float|bool
    {
        return static::values()[$key] ?? static::schema()[$key]['default'];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function saveAll(array $values): bool
    {
        foreach (static::schema() as $key => $item) {
            if (! array_key_exists($key, $values)) {
                continue;
            }
            $value = static::cast($values[$key], $item['default']);
            if (isset($item['min'])) {
                $value = max($item['min'], min($item['max'], $value));
            }
            static::query()->updateOrCreate(['key' => $key], [
                'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        Cache::forget(self::CACHE_KEY);

        return true;
    }

    protected static function cast(mixed $value, int|float|bool $default): int|float|bool
    {
        return match (true) {
            is_bool($default) => filter_var($value, FILTER_VALIDATE_BOOL),
            is_float($default) => (float) $value,
            default => (int) $value,
        };
    }
}
