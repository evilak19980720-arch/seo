<?php

declare(strict_types=1);

namespace Modules\Stats\Http\Controllers;

use Catch\Base\CatchController as Controller;
use Illuminate\Support\Facades\DB;
use Modules\Content\Enums\ArticleStatus;
use Modules\Site\Enums\OnboardStatus;
use Modules\Stats\Support\Stats;

/**
 * @group 数据统计
 *
 * @subgroup 首页面板
 */
class DashboardController extends Controller
{
    /** 面板上按 今天 / 7 天 / 30 天 展示的指标 */
    protected const PERIOD_METRICS = [Stats::CRAWL_GOOGLE, Stats::CRAWL_BING, Stats::PAGEVIEWS, Stats::CLICKS];

    public function index(): array
    {
        $today = (int) date('Ymd');
        $from7 = (int) date('Ymd', strtotime('-6 days'));
        $from30 = (int) date('Ymd', strtotime('-29 days'));

        $rows = DB::table('stats_daily')
            ->where('date', '>=', $from30)
            ->whereIn('metric', self::PERIOD_METRICS)
            ->groupBy('metric', 'date')
            ->get(['metric', 'date', DB::raw('SUM(value) as total')]);

        $periods = [];
        foreach (self::PERIOD_METRICS as $metric) {
            $periods[$metric] = ['today' => 0, 'week' => 0, 'month' => 0];
        }
        foreach ($rows as $row) {
            $total = (int) $row->total;
            $periods[$row->metric]['month'] += $total;
            if ($row->date >= $from7) {
                $periods[$row->metric]['week'] += $total;
            }
            if ((int) $row->date === $today) {
                $periods[$row->metric]['today'] += $total;
            }
        }

        $crawls = [];
        foreach (['today', 'week', 'month'] as $period) {
            $crawls[$period] = $periods[Stats::CRAWL_GOOGLE][$period] + $periods[Stats::CRAWL_BING][$period];
        }

        $sites = DB::table('sites')->where('deleted_at', 0);
        $articles = DB::table('content_articles')->where('deleted_at', 0);

        return [
            'sites' => (clone $sites)->count(),
            'sites_online' => (clone $sites)->where('onboard_status', OnboardStatus::Online->value)->count(),
            'articles' => (clone $articles)->count(),
            'articles_published' => (clone $articles)->where('status', ArticleStatus::Published->value)->count(),
            'crawls' => $crawls + ['total' => (int) DB::table('stats_daily')->whereIn('metric', [Stats::CRAWL_GOOGLE, Stats::CRAWL_BING])->sum('value')],
            'google' => $periods[Stats::CRAWL_GOOGLE],
            'bing' => $periods[Stats::CRAWL_BING],
            'pageviews' => $periods[Stats::PAGEVIEWS],
            'clicks' => $periods[Stats::CLICKS],
            'trend' => $this->trend($from30, $rows),
        ];
    }

    /**
     * 30 天每日趋势
     */
    protected function trend(int $from, $rows): array
    {
        $dates = array_map(fn ($i) => date('Ymd', strtotime("+{$i} days", strtotime((string) $from))), range(0, 29));
        $series = array_fill_keys(self::PERIOD_METRICS, array_fill_keys($dates, 0));
        foreach ($rows as $row) {
            if (isset($series[$row->metric][(string) $row->date])) {
                $series[$row->metric][(string) $row->date] = (int) $row->total;
            }
        }

        return [
            'dates' => array_map(fn ($date) => substr($date, 4, 2).'-'.substr($date, 6, 2), $dates),
            'series' => array_map('array_values', $series),
            'labels' => array_intersect_key(Stats::metrics(), $series),
        ];
    }
}
