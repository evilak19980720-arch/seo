<?php

declare(strict_types=1);

namespace Modules\Site\Models;

use Catch\Base\CatchModel as Model;
use Catch\Enums\Status;
use Catch\Exceptions\FailedException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $name
 * @property string $ip
 * @property string $region
 * @property int $capacity
 * @property int $site_count
 * @property int $status
 * @property string $remark
 */
class Server extends Model
{
    protected $table = 'site_servers';

    protected $fillable = ['id', 'name', 'ip', 'region', 'capacity', 'site_count', 'status', 'remark', 'creator_id', 'created_at', 'updated_at', 'deleted_at'];

    protected array $fields = ['id', 'name', 'ip', 'region', 'capacity', 'site_count', 'status', 'remark', 'created_at'];

    protected array $form = ['name', 'ip', 'region', 'capacity', 'status', 'remark'];

    public array $searchable = [
        'name' => 'like',
        'ip' => 'like',
        'region' => '=',
        'status' => '=',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'site_count' => 'integer',
        'status' => 'integer',
    ];

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class, 'server_id');
    }

    /**
     * 分配一台还有空位的服务器：先填满编号小的服务器，再用下一台
     */
    public function allocate(?int $preferredId = null): self
    {
        return DB::transaction(function () use ($preferredId) {
            $server = static::query()
                ->where('status', Status::Enable->value)
                ->whereColumn('site_count', '<', 'capacity')
                ->when($preferredId, fn ($query) => $query->whereKey($preferredId))
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $server) {
                throw new FailedException($preferredId ? '所选服务器已满或已禁用' : '没有可用的服务器，请先添加服务器');
            }

            $server->increment('site_count');

            return $server;
        });
    }

    public function release(int $id): void
    {
        static::query()->whereKey($id)->where('site_count', '>', 0)->decrement('site_count');
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    public function options(): array
    {
        return static::query()
            ->where('status', Status::Enable->value)
            ->orderBy('id')
            ->get(['id', 'name', 'ip', 'capacity', 'site_count'])
            ->map(fn (self $server) => [
                'label' => sprintf('%s（%s，%d/%d）', $server->name, $server->ip, $server->site_count, $server->capacity),
                'value' => $server->id,
                'disabled' => $server->site_count >= $server->capacity,
            ])->all();
    }
}
