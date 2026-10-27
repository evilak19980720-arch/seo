<?php

declare(strict_types=1);

namespace Modules\Stats\Console;

use Illuminate\Console\Command;
use Modules\Site\Enums\OnboardStatus;
use Modules\Site\Models\CloudflareAccount;
use Modules\Site\Models\Site;
use Modules\Stats\Support\Stats;
use Throwable;

/**
 * 拉取每个站点前一天的 Cloudflare 流量（请求、页面浏览、独立访客）
 */
class PullCloudflare extends Command
{
    protected $signature = 'stats:cloudflare {--date= : 日期 Y-m-d，默认昨天}';

    protected $description = '从 Cloudflare 拉取各站点每日流量';

    protected const QUERY = <<<'GQL'
query ($zones: [string!], $date: Date!) {
  viewer {
    zones(filter: { zoneTag_in: $zones }) {
      zoneTag
      httpRequests1dGroups(limit: 1, filter: { date: $date }) {
        sum { requests pageViews }
        uniq { uniques }
      }
    }
  }
}
GQL;

    public function handle(): int
    {
        $date = $this->option('date') ?: date('Y-m-d', strtotime('yesterday'));
        $day = (int) date('Ymd', strtotime($date));
        $saved = 0;

        CloudflareAccount::query()->where('status', 1)->each(function (CloudflareAccount $account) use ($date, $day, &$saved) {
            $client = $account->client();

            Site::query()
                ->where('cloudflare_account_id', $account->id)
                ->where('onboard_status', OnboardStatus::Online->value)
                ->where('zone_id', '!=', '')
                ->select(['id', 'zone_id'])
                ->chunkById(10, function ($sites) use ($client, $date, $day, &$saved) {
                    $siteIds = $sites->pluck('id', 'zone_id');
                    try {
                        $data = $client->graphql(self::QUERY, ['zones' => $siteIds->keys()->all(), 'date' => $date]);
                    } catch (Throwable $e) {
                        $this->warn($e->getMessage());

                        return;
                    }

                    foreach ($data['viewer']['zones'] ?? [] as $zone) {
                        $siteId = $siteIds[$zone['zoneTag']] ?? null;
                        $group = $zone['httpRequests1dGroups'][0] ?? null;
                        if (! $siteId || ! $group) {
                            continue;
                        }
                        Stats::set($siteId, Stats::CF_REQUESTS, (int) $group['sum']['requests'], $day);
                        Stats::set($siteId, Stats::CF_PAGEVIEWS, (int) $group['sum']['pageViews'], $day);
                        Stats::set($siteId, Stats::CF_UNIQUES, (int) $group['uniq']['uniques'], $day);
                        $saved++;
                    }
                });
        });

        $this->info("{$date} 已保存 {$saved} 个站点的流量");

        return self::SUCCESS;
    }
}
