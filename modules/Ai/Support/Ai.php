<?php

declare(strict_types=1);

namespace Modules\Ai\Support;

use Catch\Enums\Status;
use Catch\Exceptions\FailedException;
use Modules\Ai\Enums\PromptType;
use Modules\Ai\Models\CallLog;
use Modules\Ai\Models\Channel;
use Modules\Ai\Models\Prompt;
use Throwable;

/**
 * 业务模块调用 AI 的唯一入口：按类型取启用的提示词，渲染变量，调用渠道并记录日志
 */
class Ai
{
    /**
     * @param  array<string, mixed>  $variables
     */
    public function run(PromptType $type, array $variables, int $siteId = 0): ChatResult
    {
        $prompt = Prompt::query()
            ->with('channel')
            ->where('type', $type->value)
            ->where('status', Status::Enable->value)
            ->orderByDesc('id')
            ->first();

        if (! $prompt) {
            throw new FailedException("没有启用的「{$type->name()}」提示词");
        }

        $channel = $prompt->channel;
        if (! $channel || $channel->status !== Status::Enable->value) {
            throw new FailedException("提示词「{$prompt->name}」没有可用的 AI 渠道");
        }

        return $this->call($channel, $prompt, Prompt::render($prompt->system_prompt, $variables), Prompt::render($prompt->content, $variables), $siteId);
    }

    public function call(Channel $channel, ?Prompt $prompt, string $system, string $user, int $siteId = 0): ChatResult
    {
        $startedAt = microtime(true);
        $log = [
            'channel_id' => $channel->id,
            'prompt_id' => $prompt?->id ?? 0,
            'prompt_version' => $prompt?->version ?? 0,
            'type' => $prompt?->type ?? 0,
            'site_id' => $siteId,
            'model' => $channel->model,
        ];

        try {
            $result = (new AiClient($channel))->chat($system, $user);

            CallLog::query()->create($log + [
                'input_tokens' => $result->inputTokens,
                'output_tokens' => $result->outputTokens,
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'status' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            return $result;
        } catch (Throwable $e) {
            CallLog::query()->create($log + [
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'status' => 2,
                'error' => mb_substr($e->getMessage(), 0, 1000),
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            throw $e;
        }
    }
}
