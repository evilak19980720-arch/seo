<?php

declare(strict_types=1);

namespace Modules\Ad\Enums;

use Catch\Enums\Enum;

/**
 * 广告位：对应前台模板里预留的位置，每个位置每页最多一个广告
 */
enum AdPosition: string implements Enum
{
    case Header = 'header';
    case HomeMiddle = 'home_middle';
    case ArticleTop = 'article_top';
    case ArticleBottom = 'article_bottom';
    case Footer = 'footer';

    public function value(): string
    {
        return $this->value;
    }

    public function name(): string
    {
        return match ($this) {
            self::Header => '页头下方（全站）',
            self::HomeMiddle => '首页中部',
            self::ArticleTop => '文章正文上方',
            self::ArticleBottom => '文章正文下方',
            self::Footer => '页脚上方（全站）',
        };
    }

    /**
     * 文章太短时不放正文上方的广告，避免广告比内容多
     */
    public function minArticleWords(): int
    {
        return $this === self::ArticleTop ? 600 : 0;
    }
}
