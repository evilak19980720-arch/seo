<?php

declare(strict_types=1);

namespace Modules\Content\Enums;

use Catch\Enums\Enum;

enum TopicStatus: int implements Enum
{
    case Pending = 1; // 待写
    case Writing = 2; // 写作中
    case Used = 3; // 已成文
    case Dropped = 4; // 放弃

    public function value(): int
    {
        return $this->value;
    }

    public function name(): string
    {
        return match ($this) {
            self::Pending => '待写',
            self::Writing => '写作中',
            self::Used => '已成文',
            self::Dropped => '放弃',
        };
    }
}
