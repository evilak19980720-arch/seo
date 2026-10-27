<?php

declare(strict_types=1);

namespace Modules\Ai\Enums;

use Catch\Enums\Enum;

enum PromptType: int implements Enum
{
    case GameProfile = 1; // 生成游戏资料
    case Topic = 2; // 生成选题
    case ArticleGenerate = 3; // 生成文章
    case ArticlePolish = 4; // 润色文章
    case ArticleReview = 5; // 审核文章
    case Seo = 6; // 生成标题与描述
    case SiteTemplate = 7; // 生成站点模板

    public function value(): int
    {
        return $this->value;
    }

    public function name(): string
    {
        return match ($this) {
            self::GameProfile => '生成游戏资料',
            self::Topic => '生成选题',
            self::ArticleGenerate => '生成文章',
            self::ArticlePolish => '润色文章',
            self::ArticleReview => '审核文章',
            self::Seo => '生成标题与描述',
            self::SiteTemplate => '生成站点模板',
        };
    }

    /**
     * 每类提示词可用的变量，后台编辑提示词时展示
     *
     * @return array<string, string>
     */
    public function variables(): array
    {
        $site = ['site_name' => '网站名称', 'locale' => '语言代码', 'game_profile' => '游戏资料（JSON）'];

        return match ($this) {
            self::GameProfile => ['site_name' => '网站名称', 'locale' => '语言代码', 'game_brief' => '游戏说明'],
            self::Topic => $site + ['existing_titles' => '本站已有文章标题', 'count' => '需要的选题数量'],
            self::ArticleGenerate => $site + ['topic' => '选题', 'keyword' => '目标关键词', 'min_words' => '最少字数', 'max_words' => '最多字数'],
            self::ArticlePolish => $site + ['article' => '文章正文'],
            self::ArticleReview => $site + ['article' => '文章正文', 'existing_titles' => '本站已有文章标题'],
            self::Seo => $site + ['article' => '文章正文'],
            self::SiteTemplate => ['site_name' => '网站名称', 'locale' => '语言代码', 'genre' => '游戏类型', 'style_notes' => '风格说明', 'has_image' => '是否附带参考图（yes/no）'],
        };
    }
}
