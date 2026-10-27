<?php

declare(strict_types=1);

namespace Modules\Site\Models;

use Catch\Base\CatchModel as Model;
use Catch\Exceptions\FailedException;
use Illuminate\Support\Str;
use Modules\Site\Jobs\ResolveDomain;

/**
 * 批量解析任务：每个域名一条
 *
 * @property int $id
 * @property string $batch
 * @property int $cloudflare_account_id
 * @property string $zone_id
 * @property string $domain
 * @property string $ip
 * @property bool $proxied
 * @property bool $with_www
 * @property int $status
 */
class DnsTask extends Model
{
    public const PENDING = 1;

    public const SUCCESS = 2;

    public const FAILED = 3;

    protected $table = 'site_dns_tasks';

    protected $fillable = [
        'id', 'batch', 'cloudflare_account_id', 'zone_id', 'domain', 'ip', 'proxied', 'with_www', 'status', 'error',
        'creator_id', 'created_at', 'updated_at', 'deleted_at',
    ];

    protected array $fields = ['id', 'batch', 'cloudflare_account_id', 'domain', 'ip', 'proxied', 'with_www', 'status', 'error', 'created_at', 'updated_at'];

    public array $searchable = [
        'batch' => '=',
        'domain' => 'like',
        'status' => '=',
        'cloudflare_account_id' => '=',
    ];

    protected $casts = [
        'cloudflare_account_id' => 'integer',
        'proxied' => 'boolean',
        'with_www' => 'boolean',
        'status' => 'integer',
    ];

    /**
     * 为选中的域名创建解析任务并放入队列
     *
     * @param  array<int, array{id: string, name: string}>  $zones
     */
    public function createBatch(CloudflareAccount $account, array $zones, string $ip, bool $proxied, bool $withWww): string
    {
        if (! $zones) {
            throw new FailedException('请至少选择一个域名');
        }

        $batch = Str::random(12);
        foreach ($zones as $zone) {
            $id = $this->createBy([
                'batch' => $batch,
                'cloudflare_account_id' => $account->id,
                'zone_id' => (string) $zone['id'],
                'domain' => strtolower((string) $zone['name']),
                'ip' => $ip,
                'proxied' => $proxied,
                'with_www' => $withWww,
                'status' => self::PENDING,
            ]);
            ResolveDomain::dispatch((int) $id);
        }

        return $batch;
    }

    public function retry(int $id): bool
    {
        $task = static::query()->findOrFail($id);
        $task->update(['status' => self::PENDING, 'error' => '']);
        ResolveDomain::dispatch($task->id);

        return true;
    }
}
