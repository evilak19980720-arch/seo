<?php

declare(strict_types=1);

namespace Modules\Content\Enums;

use Catch\Enums\Enum;

enum ArticleStatus: int implements Enum
{
    case Writing = 1; // 生成中
    case Rejected = 2; // 审核未通过
    case Queued = 3; // 待发布
    case Published = 4; // 已发布
    case Offline = 5; // 已下线
    case Failed = 6; // 生成失败

    public function value(): int
    {
        return $this->value;
    }

    public function name(): string
    {
        return match ($this) {
            self::Writing => '生成中',
            self::Rejected => '审核未通过',
            self::Queued => '待发布',
            self::Published => '已发布',
            self::Offline => '已下线',
            self::Failed => '生成失败',
        };
    }
}
