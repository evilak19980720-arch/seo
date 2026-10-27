<?php

declare(strict_types=1);

namespace Modules\Site\Http\Controllers;

use Catch\Base\CatchController as Controller;
use Catch\Exceptions\FailedException;
use Modules\Site\Http\Requests\CloudflareAccountRequest;
use Modules\Site\Models\CloudflareAccount;
use Modules\Site\Models\Site;

/**
 * @group 站点管理
 *
 * @subgroup Cloudflare 账号
 */
class CloudflareAccountsController extends Controller
{
    public function __construct(
        protected readonly CloudflareAccount $model
    ) {
    }

    public function index(): mixed
    {
        return $this->model->getList();
    }

    public function store(CloudflareAccountRequest $request): mixed
    {
        return $this->model->storeBy($request->validated());
    }

    public function show($id): mixed
    {
        return $this->model->firstBy($id);
    }

    public function update($id, CloudflareAccountRequest $request): mixed
    {
        $data = $request->validated();
        if (empty($data['api_token'])) {
            unset($data['api_token']);
        }

        return $this->model->updateBy($id, $data);
    }

    public function destroy($id): mixed
    {
        if (Site::query()->where('cloudflare_account_id', $id)->exists()) {
            throw new FailedException('该账号下还有站点，不能删除');
        }

        return $this->model->deleteBy($id);
    }

    public function enable($id): mixed
    {
        return $this->model->toggleBy($id);
    }

    /**
     * 校验 API Token 是否可用
     */
    public function verify($id): bool
    {
        $account = $this->model->newQuery()->findOrFail($id);

        if (! $account->client()->verifyToken($account->account_id)) {
            throw new FailedException('API Token 不可用，请检查权限或是否过期');
        }

        return true;
    }

    public function options(): array
    {
        return $this->model->options();
    }
}
