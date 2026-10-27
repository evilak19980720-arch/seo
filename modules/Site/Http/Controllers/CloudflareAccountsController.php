<?php

declare(strict_types=1);

namespace Modules\Site\Http\Controllers;

use Catch\Base\CatchController as Controller;
use Catch\Exceptions\FailedException;
use Illuminate\Http\Request;
use Modules\Site\Http\Requests\CloudflareAccountRequest;
use Modules\Site\Models\CloudflareAccount;
use Modules\Site\Models\DnsTask;
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

    /**
     * 账号下的域名列表（来自 Cloudflare）
     */
    public function zones($id, Request $request): array
    {
        $account = $this->model->newQuery()->findOrFail($id);
        $result = $account->client()->listZones($account->account_id, max(1, $request->integer('page', 1)), trim((string) $request->input('name')));

        // 标出已经添加为站点的域名
        $sites = Site::query()->whereIn('domain', array_column($result['items'], 'name'))->pluck('ip', 'domain');
        foreach ($result['items'] as &$zone) {
            $zone['site_ip'] = $sites[$zone['name']] ?? null;
        }

        return $result;
    }

    /**
     * 批量解析选中的域名
     */
    public function resolve($id, Request $request, DnsTask $task): string
    {
        $data = $request->validate([
            'zones' => ['required', 'array', 'min:1', 'max:500'],
            'zones.*.id' => ['required', 'string', 'max:64'],
            'zones.*.name' => ['required', 'string', 'max:191'],
            'ip' => ['required', 'ip'],
            'proxied' => ['boolean'],
            'with_www' => ['boolean'],
        ], [], ['zones' => '域名', 'ip' => '解析 IP']);

        $account = $this->model->newQuery()->findOrFail($id);

        return $task->createBatch($account, $data['zones'], $data['ip'], (bool) ($data['proxied'] ?? true), (bool) ($data['with_www'] ?? true));
    }
}
