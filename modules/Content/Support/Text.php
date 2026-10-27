<?php

declare(strict_types=1);

namespace Modules\Content\Support;

use Illuminate\Support\Str;

/**
 * 文章文本工具：字数、slug、标题、相似度
 */
class Text
{
    /**
     * 去掉 Markdown 标记后的纯文本
     */
    public static function plain(string $markdown): string
    {
        $text = preg_replace('/```.*?```/s', ' ', $markdown) ?? $markdown;
        $text = preg_replace('/!\[[^\]]*\]\([^)]*\)/', ' ', $text) ?? $text;
        $text = preg_replace('/\[([^\]]*)\]\([^)]*\)/', '$1', $text) ?? $text;
        $text = preg_replace('/[#>*_`~|-]+/', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? $text);
    }

    public static function wordCount(string $markdown): int
    {
        return preg_match_all('/[\p{L}\p{N}]+(?:[\'’-][\p{L}\p{N}]+)*/u', static::plain($markdown));
    }

    public static function slug(string $value): string
    {
        return Str::limit(Str::slug(Str::ascii($value)), 80, '');
    }

    /**
     * 第一行 "# " 标题
     */
    public static function title(string $markdown): string
    {
        if (preg_match('/^\s*#\s+(.+)$/m', $markdown, $matches)) {
            return trim($matches[1], " \t#");
        }

        return '';
    }

    /**
     * 去掉开头的 "# " 标题行，正文里标题单独存储
     */
    public static function withoutTitle(string $markdown): string
    {
        return ltrim(preg_replace('/^\s*#\s+.+$/m', '', $markdown, 1) ?? $markdown);
    }

    /**
     * 基于 5 词 shingle 的 Jaccard 相似度（0–1）
     */
    public static function similarity(string $a, string $b, int $size = 5): float
    {
        $sa = static::shingles($a, $size);
        $sb = static::shingles($b, $size);
        if (! $sa || ! $sb) {
            return 0.0;
        }

        $intersect = count(array_intersect_key($sa, $sb));

        return $intersect / (count($sa) + count($sb) - $intersect);
    }

    /**
     * @return array<string, true>
     */
    protected static function shingles(string $markdown, int $size): array
    {
        $words = preg_split('/\s+/u', mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', static::plain($markdown)) ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $shingles = [];
        for ($i = 0, $n = count($words) - $size; $i <= $n; $i++) {
            $shingles[implode(' ', array_slice($words, $i, $size))] = true;
        }

        return $shingles;
    }
}
