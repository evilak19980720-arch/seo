<?php

declare(strict_types=1);

namespace Modules\Site\Http\Controllers;

use Catch\Base\CatchController as Controller;
use Catch\Exceptions\FailedException;
use Modules\Site\Http\Requests\ServerRequest;
use Modules\Site\Models\Server;
use Modules\Site\Models\Site;

/**
 * @group 站点管理
 *
 * @subgroup 服务器
 */
class ServersController extends Controller
{
    public function __construct(
        protected readonly Server $model
    ) {
    }

    public function index(): mixed
    {
        return $this->model->getList();
    }

    public function store(ServerRequest $request): mixed
    {
        return $this->model->storeBy($request->validated());
    }

    public function show($id): mixed
    {
        return $this->model->firstBy($id);
    }

    public function update($id, ServerRequest $request): mixed
    {
        return $this->model->updateBy($id, $request->validated());
    }

    public function destroy($id): mixed
    {
        if (Site::query()->where('server_id', $id)->exists()) {
            throw new FailedException('服务器上还有站点，请先迁移或删除站点');
        }

        return $this->model->deleteBy($id);
    }

    public function enable($id): mixed
    {
        return $this->model->toggleBy($id);
    }

    public function options(): array
    {
        return $this->model->options();
    }
}
