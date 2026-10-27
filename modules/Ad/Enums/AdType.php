<?php

declare(strict_types=1);

namespace Modules\Ad\Enums;

use Catch\Enums\Enum;

enum AdType: int implements Enum
{
    case Image = 1; // 图片 + 链接
    case Html = 2; // 自定义代码

    public function value(): int
    {
        return $this->value;
    }

    public function name(): string
    {
        return match ($this) {
            self::Image => '图片广告',
            self::Html => '代码广告',
        };
    }
}
