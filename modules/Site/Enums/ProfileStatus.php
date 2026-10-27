<?php

declare(strict_types=1);

namespace Modules\Site\Enums;

use Catch\Enums\Enum;

enum ProfileStatus: int implements Enum
{
    case Generating = 1; // 生成中
    case Draft = 2; // 待确认
    case Confirmed = 3; // 已确认
    case Failed = 4; // 生成失败

    public function value(): int
    {
        return $this->value;
    }

    public function name(): string
    {
        return match ($this) {
            self::Generating => '生成中',
            self::Draft => '待确认',
            self::Confirmed => '已确认',
            self::Failed => '生成失败',
        };
    }
}
