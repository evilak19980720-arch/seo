<?php

declare(strict_types=1);

namespace Modules\Ai\Http\Controllers;

use Catch\Base\CatchController as Controller;
use Modules\Ai\Enums\PromptType;
use Modules\Ai\Http\Requests\PromptRequest;
use Modules\Ai\Models\Prompt;

/**
 * @group AI 管理
 *
 * @subgroup 提示词
 */
class PromptsController extends Controller
{
    public function __construct(
        protected readonly Prompt $model
    ) {
    }

    public function index(): mixed
    {
        return $this->model->setBeforeGetList(fn ($query) => $query->with('channel:id,name,model'))->getList();
    }

    public function store(PromptRequest $request): mixed
    {
        return $this->model->storeBy($request->validated());
    }

    public function show($id): mixed
    {
        return $this->model->firstBy($id);
    }

    public function update($id, PromptRequest $request): mixed
    {
        return $this->model->updatePrompt((int) $id, $request->validated());
    }

    public function destroy($id): mixed
    {
        return $this->model->deleteBy($id);
    }

    public function enable($id): mixed
    {
        return $this->model->toggleBy($id);
    }

    /**
     * 提示词类型及其可用变量
     *
     * @return array<int, array{label: string, value: int, variables: array<string, string>}>
     */
    public function types(): array
    {
        return array_map(fn (PromptType $type) => [
            'label' => $type->name(),
            'value' => $type->value,
            'variables' => $type->variables(),
        ], PromptType::cases());
    }
}
