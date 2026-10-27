<?php

declare(strict_types=1);

namespace Modules\Ai\Http\Controllers;

use Catch\Base\CatchController as Controller;
use Catch\Exceptions\FailedException;
use Modules\Ai\Http\Requests\ChannelRequest;
use Modules\Ai\Models\Channel;
use Modules\Ai\Models\Prompt;
use Modules\Ai\Support\Ai;

/**
 * @group AI 管理
 *
 * @subgroup AI 渠道
 */
class ChannelsController extends Controller
{
    public function __construct(
        protected readonly Channel $model
    ) {
    }

    public function index(): mixed
    {
        return $this->model->getList();
    }

    public function store(ChannelRequest $request): mixed
    {
        return $this->model->storeBy($request->validated());
    }

    public function show($id): mixed
    {
        return $this->model->firstBy($id);
    }

    public function update($id, ChannelRequest $request): mixed
    {
        $data = $request->validated();
        if (empty($data['api_key'])) {
            unset($data['api_key']);
        }

        return $this->model->updateBy($id, $data);
    }

    public function destroy($id): mixed
    {
        if (Prompt::query()->where('channel_id', $id)->exists()) {
            throw new FailedException('还有提示词在使用这个渠道，不能删除');
        }

        return $this->model->deleteBy($id);
    }

    public function enable($id): mixed
    {
        return $this->model->toggleBy($id);
    }

    /**
     * 发送一条测试消息，返回模型回复和耗时
     *
     * @return array{reply: string, input_tokens: int, output_tokens: int}
     */
    public function test($id, Ai $ai): array
    {
        $channel = $this->model->newQuery()->findOrFail($id);
        $result = $ai->call($channel, null, '', 'Responda apenas: OK');

        return ['reply' => $result->text, 'input_tokens' => $result->inputTokens, 'output_tokens' => $result->outputTokens];
    }

    public function options(): array
    {
        return $this->model->options();
    }
}
