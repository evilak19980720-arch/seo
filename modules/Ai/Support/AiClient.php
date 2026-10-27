<?php

declare(strict_types=1);

namespace Modules\Ai\Support;

use Catch\Exceptions\FailedException;
use Illuminate\Support\Facades\Http;
use Modules\Ai\Enums\Provider;
use Modules\Ai\Models\Channel;

/**
 * 按渠道协议调用聊天接口
 */
class AiClient
{
    public function __construct(
        protected readonly Channel $channel
    ) {
    }

    /**
     * @param  array<int, array{mime: string, data: string}>  $images  base64 图片，模型需要支持识图
     */
    public function chat(string $system, string $user, array $images = []): ChatResult
    {
        return match ($this->channel->provider) {
            Provider::Anthropic->value => $this->anthropic($system, $user, $images),
            default => $this->openAi($system, $user, $images),
        };
    }

    protected function openAi(string $system, string $user, array $images = []): ChatResult
    {
        $messages = [];
        if ($system !== '') {
            $messages[] = ['role' => 'system', 'content' => $system];
        }
        $content = $user;
        if ($images) {
            $content = [['type' => 'text', 'text' => $user]];
            foreach ($images as $image) {
                $content[] = ['type' => 'image_url', 'image_url' => ['url' => "data:{$image['mime']};base64,{$image['data']}"]];
            }
        }
        $messages[] = ['role' => 'user', 'content' => $content];

        $body = $this->post(rtrim($this->channel->base_url, '/').'/chat/completions', [
            'Authorization' => 'Bearer '.$this->channel->api_key,
        ], [
            'model' => $this->channel->model,
            'messages' => $messages,
            'temperature' => $this->channel->temperature,
            'max_tokens' => $this->channel->max_tokens,
        ]);

        return new ChatResult(
            (string) ($body['choices'][0]['message']['content'] ?? ''),
            (int) ($body['usage']['prompt_tokens'] ?? 0),
            (int) ($body['usage']['completion_tokens'] ?? 0),
        );
    }

    protected function anthropic(string $system, string $user, array $images = []): ChatResult
    {
        $content = $user;
        if ($images) {
            $content = array_map(fn ($image) => [
                'type' => 'image',
                'source' => ['type' => 'base64', 'media_type' => $image['mime'], 'data' => $image['data']],
            ], $images);
            $content[] = ['type' => 'text', 'text' => $user];
        }

        $payload = [
            'model' => $this->channel->model,
            'max_tokens' => $this->channel->max_tokens,
            'temperature' => $this->channel->temperature,
            'messages' => [['role' => 'user', 'content' => $content]],
        ];
        if ($system !== '') {
            $payload['system'] = $system;
        }

        $body = $this->post(rtrim($this->channel->base_url, '/').'/v1/messages', [
            'x-api-key' => $this->channel->api_key,
            'anthropic-version' => '2023-06-01',
        ], $payload);

        $text = collect($body['content'] ?? [])->where('type', 'text')->pluck('text')->implode('');

        return new ChatResult(
            $text,
            (int) ($body['usage']['input_tokens'] ?? 0),
            (int) ($body['usage']['output_tokens'] ?? 0),
        );
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function post(string $url, array $headers, array $payload): array
    {
        $response = Http::withHeaders($headers)
            ->acceptJson()
            ->timeout($this->channel->timeout ?: 120)
            ->post($url, $payload);

        if (! $response->successful()) {
            $message = $response->json('error.message') ?? mb_substr($response->body(), 0, 300);

            throw new FailedException("AI 接口错误（HTTP {$response->status()}）：{$message}");
        }

        return $response->json() ?? [];
    }
}
