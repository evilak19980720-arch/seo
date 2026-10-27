<?php

declare(strict_types=1);

namespace Modules\Site\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Site\Models\CloudflareAccount;
use Modules\Site\Models\DnsTask;
use Modules\Site\Models\Site;
use Throwable;

/**
 * 执行一条批量解析任务
 */
class ResolveDomain implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $taskId
    ) {
    }

    public function backoff(): array
    {
        return [20, 60];
    }

    public function handle(): void
    {
        $task = DnsTask::query()->find($this->taskId);
        if (! $task || $task->status === DnsTask::SUCCESS) {
            return;
        }

        $account = CloudflareAccount::query()->findOrFail($task->cloudflare_account_id);
        $account->client()->pointDomain($task->zone_id, $task->domain, $task->ip, $task->proxied, $task->with_www);

        $task->update(['status' => DnsTask::SUCCESS, 'error' => '']);

        // 已经添加到系统的站点同步记录新 IP
        Site::query()->where('domain', $task->domain)->where('cloudflare_account_id', $account->id)
            ->update(['ip' => $task->ip, 'zone_id' => $task->zone_id]);
    }

    public function failed(?Throwable $exception): void
    {
        DnsTask::query()->whereKey($this->taskId)->update([
            'status' => DnsTask::FAILED,
            'error' => mb_substr($exception?->getMessage() ?: '解析失败', 0, 1000),
        ]);
    }
}
