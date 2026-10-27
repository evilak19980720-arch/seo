<?php

declare(strict_types=1);

namespace Modules\Site\Http\Controllers;

use Catch\Base\CatchController as Controller;
use Modules\Site\Models\DnsTask;

/**
 * @group 站点管理
 *
 * @subgroup 批量解析记录
 */
class DnsTasksController extends Controller
{
    public function __construct(
        protected readonly DnsTask $model
    ) {
    }

    public function index(): mixed
    {
        return $this->model->getList();
    }

    public function retry($id): bool
    {
        return $this->model->retry((int) $id);
    }

    public function destroy($id): mixed
    {
        return $this->model->deleteBy($id);
    }
}
