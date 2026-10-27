<?php

declare(strict_types=1);

namespace Modules\Site\Http\Controllers;

use Catch\Base\CatchController as Controller;
use Modules\Site\Http\Requests\SiteRequest;
use Modules\Site\Models\Site;

/**
 * @group 站点管理
 *
 * @subgroup 站点
 */
class SitesController extends Controller
{
    public function __construct(
        protected readonly Site $model
    ) {
    }

    public function index(): mixed
    {
        return $this->model->setBeforeGetList(fn ($query) => $query->with([
            'server:id,name,ip',
            'cloudflareAccount:id,name',
            'profile:id,site_id,status',
        ]))->getList();
    }

    public function store(SiteRequest $request): int
    {
        return $this->model->createSite($request->validated());
    }

    public function show($id): mixed
    {
        return $this->model->firstBy($id);
    }

    public function update($id, SiteRequest $request): mixed
    {
        return $this->model->updateSite((int) $id, $request->validated());
    }

    public function destroy($id): mixed
    {
        return $this->model->deleteSite((int) $id);
    }

    public function enable($id): mixed
    {
        return $this->model->toggleBy($id);
    }

    /**
     * 重新执行域名接入
     */
    public function onboard($id): bool
    {
        return $this->model->retryOnboard((int) $id);
    }
}
