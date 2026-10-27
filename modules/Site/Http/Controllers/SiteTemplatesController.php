<?php

declare(strict_types=1);

namespace Modules\Site\Http\Controllers;

use Catch\Base\CatchController as Controller;
use Illuminate\Http\Request;
use Modules\Front\Support\CurrentSite;
use Modules\Site\Models\GameProfile;
use Modules\Site\Models\Site;
use Modules\Site\Models\SiteTemplate;
use Modules\Site\Support\TemplateSanitizer;

/**
 * @group 站点管理
 *
 * @subgroup 站点模板
 */
class SiteTemplatesController extends Controller
{
    public function __construct(
        protected readonly SiteTemplate $model
    ) {
    }

    public function show($siteId): SiteTemplate
    {
        Site::query()->findOrFail($siteId);

        return SiteTemplate::forSite((int) $siteId);
    }

    public function update($siteId, Request $request): bool
    {
        Site::query()->findOrFail($siteId);
        $data = $request->validate([
            'reference_image' => ['nullable', 'string', 'max:500'],
            'style_notes' => ['nullable', 'string', 'max:1000'],
            'css' => ['nullable', 'string', 'max:'.TemplateSanitizer::MAX_CSS],
            'header_html' => ['nullable', 'string', 'max:'.TemplateSanitizer::MAX_HTML],
            'footer_html' => ['nullable', 'string', 'max:'.TemplateSanitizer::MAX_HTML],
        ]);

        return $this->model->saveForSite((int) $siteId, array_map(fn ($value) => $value ?? '', $data));
    }

    public function generate($siteId): bool
    {
        Site::query()->findOrFail($siteId);

        return $this->model->generate((int) $siteId);
    }

    public function publish($siteId): bool
    {
        return $this->model->publish((int) $siteId);
    }

    public function unpublish($siteId): bool
    {
        return $this->model->unpublish((int) $siteId);
    }

    /**
     * 用草稿模板渲染首页，供后台预览（不经过前台缓存，不对外公开）
     */
    public function preview($siteId): array
    {
        $site = Site::query()->findOrFail($siteId);
        $template = SiteTemplate::forSite($site->id);
        $current = new CurrentSite($site, GameProfile::forSite($site->id), $template->draft());

        app()->instance(CurrentSite::class, $current);
        try {
            $html = app(\Modules\Front\Http\Controllers\PageController::class)->home()->getContent();
        } finally {
            app()->forgetInstance(CurrentSite::class);
        }

        return ['html' => $html];
    }
}
