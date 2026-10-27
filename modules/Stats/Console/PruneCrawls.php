<?php

declare(strict_types=1);

namespace Modules\Stats\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneCrawls extends Command
{
    protected $signature = 'stats:prune {--days=30}';

    protected $description = '删除过期的蜘蛛抓取记录（每日统计保留）';

    public function handle(): int
    {
        $before = time() - (int) $this->option('days') * 86400;
        do {
            $deleted = DB::table('stats_crawls')->where('created_at', '<', $before)->limit(5000)->delete();
        } while ($deleted > 0);

        return self::SUCCESS;
    }
}
