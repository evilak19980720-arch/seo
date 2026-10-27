<?php

declare(strict_types=1);

namespace Modules\Ai\Support;

use Catch\Exceptions\FailedException;

class ChatResult
{
    public function __construct(
        public readonly string $text,
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
    ) {
    }

    /**
     * 解析模型返回的 JSON（兼容包在 ```json 代码块里的情况）
     *
     * @return array<mixed>
     */
    public function json(): array
    {
        $text = trim($this->text);

        if (preg_match('/```(?:json)?\s*(.+?)\s*```/s', $text, $matches)) {
            $text = $matches[1];
        }

        $start = strcspn($text, '{[');
        $text = substr($text, $start);

        $data = json_decode($text, true);

        if (! is_array($data)) {
            throw new FailedException('AI 返回的不是有效 JSON：'.mb_substr($this->text, 0, 200));
        }

        return $data;
    }
}
