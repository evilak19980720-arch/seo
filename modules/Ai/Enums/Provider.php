<?php

declare(strict_types=1);

namespace Modules\Ai\Enums;

use Catch\Enums\Enum;

/**
 * AI 渠道协议。大多数国内外模型（DeepSeek、通义千问、Kimi、OpenAI 等）都兼容 OpenAI 协议
 */
enum Provider: int implements Enum
{
    case OpenAi = 1; // OpenAI 兼容协议
    case Anthropic = 2; // Anthropic Messages API

    public function value(): int
    {
        return $this->value;
    }

    public function name(): string
    {
        return match ($this) {
            self::OpenAi => 'OpenAI 兼容',
            self::Anthropic => 'Anthropic',
        };
    }
}
