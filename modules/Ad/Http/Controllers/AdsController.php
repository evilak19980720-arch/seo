<?php

declare(strict_types=1);

namespace Modules\Ad\Http\Controllers;

use Catch\Base\CatchController as Controller;
use Modules\Ad\Enums\AdPosition;
use Modules\Ad\Http\Requests\AdRequest;
use Modules\Ad\Models\Ad;

/**
 * @group 广告管理
 *
 * @subgroup 广告
 */
class AdsController extends Controller
{
    public function __construct(
        protected readonly Ad $model
    ) {
    }

    public function index(): mixed
    {
        return $this->model->getList();
    }

    public function store(AdRequest $request): mixed
    {
        return $this->model->storeBy($request->validated());
    }

    public function show($id): mixed
    {
        return $this->model->firstBy($id);
    }

    public function update($id, AdRequest $request): mixed
    {
        return $this->model->updateBy($id, $request->validated());
    }

    public function destroy($id): mixed
    {
        return $this->model->deleteBy($id);
    }

    public function enable($id): mixed
    {
        return $this->model->toggleBy($id);
    }

    public function positions(): array
    {
        return array_map(fn (AdPosition $position) => ['label' => $position->name(), 'value' => $position->value], AdPosition::cases());
    }
}
