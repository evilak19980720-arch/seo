<?php

declare(strict_types=1);

namespace Modules\Stats\Http\Controllers;

use Catch\Base\CatchController as Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group 数据统计
 *
 * @subgroup 蜘蛛抓取记录
 */
class CrawlsController extends Controller
{
    public function index(Request $request): mixed
    {
        return DB::table('stats_crawls as c')
            ->leftJoin('sites', 'sites.id', '=', 'c.site_id')
            ->when($request->integer('site_id'), fn ($q, $id) => $q->where('c.site_id', $id))
            ->when($request->input('bot'), fn ($q, $bot) => $q->where('c.bot', $bot))
            ->when($request->input('status'), fn ($q, $status) => $q->where('c.status', $status))
            ->orderByDesc('c.id')
            ->select(['c.*', 'sites.name as site_name', 'sites.domain'])
            ->paginate($request->integer('limit', 20));
    }
}
