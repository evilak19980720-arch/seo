<?php

declare(strict_types=1);

namespace Modules\Ad\Support;

use Modules\Ad\Enums\AdPosition;
use Modules\Ad\Enums\AdType;
use Modules\Ad\Models\Ad;
use Modules\Front\Support\CurrentSite;

/**
 * 输出前台广告位的 HTML。每个位置最多一个广告，按权重随机选择
 */
class AdRenderer
{
    public function __invoke(string $slot, CurrentSite $current, array $context = []): string
    {
        $position = AdPosition::tryFrom($slot);
        if (! $position) {
            return '';
        }

        $words = (int) ($context['article']->word_count ?? 0);
        if (isset($context['article']) && $words < $position->minArticleWords()) {
            return '';
        }

        $siteId = $current->site->id;
        $candidates = array_values(array_filter(
            Ad::active()[$position->value] ?? [],
            fn ($ad) => ! $ad['site_ids'] || in_array($siteId, array_map('intval', $ad['site_ids']), true)
        ));

        $ad = $this->pick($candidates);

        return $ad ? $this->html($ad) : '';
    }

    /**
     * @param  array<int, array<string, mixed>>  $ads
     */
    protected function pick(array $ads): ?array
    {
        $total = array_sum(array_map(fn ($ad) => max(1, (int) $ad['weight']), $ads));
        if ($total === 0) {
            return null;
        }

        $roll = random_int(1, $total);
        foreach ($ads as $ad) {
            $roll -= max(1, (int) $ad['weight']);
            if ($roll <= 0) {
                return $ad;
            }
        }

        return null;
    }

    protected function html(array $ad): string
    {
        $label = '<div style="font-size:12px;color:var(--muted);margin-bottom:4px">Publicidade</div>';

        if ((int) $ad['type'] === AdType::Html->value) {
            return $label.(string) $ad['html'];
        }

        if (! $ad['image'] || ! $ad['link']) {
            return '';
        }

        $size = $ad['width'] && $ad['height'] ? sprintf(' width="%d" height="%d"', $ad['width'], $ad['height']) : '';

        return $label.sprintf(
            '<a href="/go/%d" rel="sponsored nofollow noopener" target="_blank"><img src="%s" alt="%s"%s loading="lazy" style="display:block;margin:0 auto;border-radius:8px"></a>',
            $ad['id'],
            e(CurrentSite::asset($ad['image'])),
            e($ad['alt'] ?: 'Publicidade'),
            $size
        );
    }
}
