<?php

declare(strict_types=1);

namespace Modules\Stats\Http\Controllers;

use Catch\Base\CatchController as Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Content\Enums\ArticleStatus;
use Modules\Site\Enums\OnboardStatus;
use Modules\Site\Enums\ProfileStatus;
use Modules\Stats\Support\Stats;

/**
 * @group 数据统计
 *
 * @subgroup 概览
 */
class OverviewController extends Controller
{
    public function index(Request $request): array
    {
        $days = max(7, min(90, (int) $request->input('days', 30)));
        $siteId = (int) $request->input('site_id', 0);
        $from = strtotime('today') - ($days - 1) * 86400;
        $dates = array_map(fn ($i) => date('Ymd', $from + $i * 86400), range(0, $days - 1));

        return [
            'cards' => $this->cards($siteId),
            'dates' => array_map(fn ($date) => substr($date, 4, 2).'-'.substr($date, 6, 2), $dates),
            'series' => $this->series($dates, $siteId, $from),
            'metrics' => Stats::metrics() + ['articles' => '发布文章'],
            'top' => $siteId ? [] : $this->top((int) $dates[0], (string) $request->input('metric', Stats::CRAWL_GOOGLE)),
        ];
    }

    protected function cards(int $siteId): array
    {
        $weekAgo = (int) date('Ymd', strtotime('-6 days'));
        $sites = DB::table('sites')->where('deleted_at', 0)->when($siteId, fn ($q) => $q->where('id', $siteId));
        $articles = DB::table('content_articles')->where('deleted_at', 0)->when($siteId, fn ($q) => $q->where('site_id', $siteId));
        $stats = DB::table('stats_daily')->where('date', '>=', $weekAgo)->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->groupBy('metric')->pluck(DB::raw('SUM(value) as total'), 'metric');
        $ai = DB::table('ai_call_logs')->where('created_at', '>=', strtotime('-6 days', strtotime('today')))->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->selectRaw('COUNT(*) as calls, COALESCE(SUM(input_tokens + output_tokens), 0) as tokens, COALESCE(SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END), 0) as failed')->first();

        return [
            'sites' => (clone $sites)->count(),
            'sites_online' => (clone $sites)->where('onboard_status', OnboardStatus::Online->value)->count(),
            'profiles_pending' => DB::table('site_game_profiles')->where('deleted_at', 0)->where('status', ProfileStatus::Draft->value)->when($siteId, fn ($q) => $q->where('site_id', $siteId))->count(),
            'articles_published' => (clone $articles)->where('status', ArticleStatus::Published->value)->count(),
            'articles_week' => (clone $articles)->where('status', ArticleStatus::Published->value)->where('published_at', '>=', strtotime('-6 days', strtotime('today')))->count(),
            'articles_queued' => (clone $articles)->where('status', ArticleStatus::Queued->value)->count(),
            'articles_rejected' => (clone $articles)->whereIn('status', [ArticleStatus::Rejected->value, ArticleStatus::Failed->value])->count(),
            'crawl_google_week' => (int) ($stats[Stats::CRAWL_GOOGLE] ?? 0),
            'crawl_bing_week' => (int) ($stats[Stats::CRAWL_BING] ?? 0),
            'pageviews_week' => (int) ($stats[Stats::CF_PAGEVIEWS] ?? 0),
            'ad_clicks_week' => (int) ($stats[Stats::AD_CLICKS] ?? 0),
            'ai_calls_week' => (int) ($ai->calls ?? 0),
            'ai_tokens_week' => (int) ($ai->tokens ?? 0),
            'ai_failed_week' => (int) ($ai->failed ?? 0),
        ];
    }

    /**
     * @param  array<int, string>  $dates
     */
    protected function series(array $dates, int $siteId, int $from): array
    {
        $rows = DB::table('stats_daily')
            ->where('date', '>=', (int) $dates[0])
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->groupBy('metric', 'date')
            ->get(['metric', 'date', DB::raw('SUM(value) as total')]);

        $series = [];
        foreach (array_keys(Stats::metrics()) as $metric) {
            $series[$metric] = array_fill_keys($dates, 0);
        }
        foreach ($rows as $row) {
            if (isset($series[$row->metric][(string) $row->date])) {
                $series[$row->metric][(string) $row->date] = (int) $row->total;
            }
        }

        $series['articles'] = array_fill_keys($dates, 0);
        DB::table('content_articles')
            ->where('deleted_at', 0)
            ->where('status', ArticleStatus::Published->value)
            ->where('published_at', '>=', $from)
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->pluck('published_at')
            ->each(function ($time) use (&$series) {
                $date = date('Ymd', (int) $time);
                if (isset($series['articles'][$date])) {
                    $series['articles'][$date]++;
                }
            });

        return array_map('array_values', $series);
    }

    protected function top(int $fromDate, string $metric): array
    {
        if (! array_key_exists($metric, Stats::metrics())) {
            $metric = Stats::CRAWL_GOOGLE;
        }

        return DB::table('stats_daily as s')
            ->join('sites', 'sites.id', '=', 's.site_id')
            ->where('s.metric', $metric)
            ->where('s.date', '>=', $fromDate)
            ->groupBy('s.site_id', 'sites.name', 'sites.domain')
            ->orderByDesc('total')
            ->limit(20)
            ->get(['s.site_id', 'sites.name', 'sites.domain', DB::raw('SUM(s.value) as total')])
            ->all();
    }
}
