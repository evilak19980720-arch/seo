<?php

declare(strict_types=1);

namespace Modules\Site\Enums;

use Catch\Enums\Enum;

enum OnboardStatus: int implements Enum
{
    case Pending = 1; // 待接入
    case WaitingNameServers = 2; // 等待 NS 生效
    case DnsReady = 3; // 解析已写入，等待证书
    case Online = 4; // 已上线
    case Failed = 5; // 失败

    public function value(): int
    {
        return $this->value;
    }

    public function name(): string
    {
        return match ($this) {
            self::Pending => '待接入',
            self::WaitingNameServers => '等待 NS 生效',
            self::DnsReady => '等待证书',
            self::Online => '已上线',
            self::Failed => '失败',
        };
    }
}
