<?php

declare(strict_types=1);

namespace Modules\Site\Http\Controllers;

use Catch\Base\CatchController as Controller;
use Modules\Site\Http\Requests\GameProfileRequest;
use Modules\Site\Models\GameProfile;
use Modules\Site\Models\Site;

/**
 * @group 站点管理
 *
 * @subgroup 游戏资料
 */
class GameProfilesController extends Controller
{
    public function __construct(
        protected readonly GameProfile $model
    ) {
    }

    public function show($siteId): GameProfile
    {
        Site::query()->findOrFail($siteId);

        return GameProfile::forSite((int) $siteId);
    }

    public function update($siteId, GameProfileRequest $request): bool
    {
        Site::query()->findOrFail($siteId);

        return $this->model->saveForSite((int) $siteId, $request->validated());
    }

    public function regenerate($siteId): bool
    {
        Site::query()->findOrFail($siteId);

        return $this->model->regenerate((int) $siteId);
    }

    public function confirm($siteId): bool
    {
        return $this->model->confirm((int) $siteId);
    }

    public function unconfirm($siteId): bool
    {
        return $this->model->unconfirm((int) $siteId);
    }
}
