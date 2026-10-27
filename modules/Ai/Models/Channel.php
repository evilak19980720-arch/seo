<?php

declare(strict_types=1);

namespace Modules\Ai\Models;

use Catch\Base\CatchModel as Model;
use Catch\Enums\Status;

/**
 * @property int $id
 * @property string $name
 * @property int $provider
 * @property string $base_url
 * @property string $api_key
 * @property string $model
 * @property float $temperature
 * @property int $max_tokens
 * @property int $timeout
 * @property int $status
 */
class Channel extends Model
{
    protected $table = 'ai_channels';

    protected $fillable = ['id', 'name', 'provider', 'base_url', 'api_key', 'model', 'temperature', 'max_tokens', 'timeout', 'status', 'remark', 'creator_id', 'created_at', 'updated_at', 'deleted_at'];

    protected $hidden = ['api_key'];

    protected array $fields = ['id', 'name', 'provider', 'base_url', 'model', 'temperature', 'max_tokens', 'timeout', 'status', 'remark', 'created_at'];

    protected array $form = ['name', 'provider', 'base_url', 'api_key', 'model', 'temperature', 'max_tokens', 'timeout', 'status', 'remark'];

    public array $searchable = [
        'name' => 'like',
        'provider' => '=',
        'status' => '=',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'provider' => 'integer',
        'temperature' => 'float',
        'max_tokens' => 'integer',
        'timeout' => 'integer',
        'status' => 'integer',
    ];

    /**
     * @return array<int, array{label: string, value: int}>
     */
    public function options(): array
    {
        return static::query()
            ->where('status', Status::Enable->value)
            ->orderBy('id')
            ->get(['id', 'name', 'model'])
            ->map(fn (self $channel) => ['label' => "{$channel->name}（{$channel->model}）", 'value' => $channel->id])
            ->all();
    }
}
