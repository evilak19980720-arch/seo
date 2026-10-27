<?php

declare(strict_types=1);

namespace Modules\Ai\Http\Controllers;

use Catch\Base\CatchController as Controller;
use Illuminate\Support\Facades\DB;
use Modules\Ai\Models\CallLog;

/**
 * @group AI 管理
 *
 * @subgroup 调用日志
 */
class CallLogsController extends Controller
{
    public function __construct(
        protected readonly CallLog $model
    ) {
    }

    public function index(): mixed
    {
        return $this->model->setBeforeGetList(fn ($query) => $query->with(['channel:id,name', 'prompt:id,name']))->getList();
    }

    /**
     * 最近 30 天按天汇总的调用次数和 token 用量
     */
    public function summary(): mixed
    {
        $since = strtotime('-30 days 00:00:00');

        return CallLog::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('FLOOR(created_at / 86400) as day, COUNT(*) as calls, SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as failed, SUM(input_tokens) as input_tokens, SUM(output_tokens) as output_tokens')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'date' => date('Y-m-d', (int) $row->day * 86400),
                'calls' => (int) $row->calls,
                'failed' => (int) $row->failed,
                'input_tokens' => (int) $row->input_tokens,
                'output_tokens' => (int) $row->output_tokens,
            ]);
    }
}
