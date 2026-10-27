<?php

declare(strict_types=1);

namespace Modules\Site\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Modules\Ai\Enums\PromptType;
use Modules\Ai\Support\Ai;
use Modules\Site\Models\GameProfile;
use Modules\Site\Models\Site;
use Modules\Site\Models\SiteTemplate;
use Modules\Site\Support\TemplateSanitizer;
use RuntimeException;
use Throwable;

/**
 * 用参考图和游戏信息让 AI 生成站点模板草稿（CSS + 页头 + 页脚），清理后等待人工确认
 */
class GenerateSiteTemplate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(
        public readonly int $siteId
    ) {
    }

    public function backoff(): array
    {
        return [120];
    }

    public function handle(Ai $ai): void
    {
        $site = Site::query()->find($this->siteId);
        if (! $site) {
            return;
        }
        $template = SiteTemplate::forSite($site->id);
        $image = $this->image($template->reference_image);

        $data = $ai->run(PromptType::SiteTemplate, [
            'site_name' => $site->name,
            'locale' => $site->locale,
            'genre' => GameProfile::query()->where('site_id', $site->id)->value('genre') ?: '(unknown)',
            'style_notes' => $template->style_notes ?: '(none)',
            'has_image' => $image ? 'yes' : 'no',
        ], $site->id, $image ? [$image] : [])->json();

        $css = TemplateSanitizer::css((string) ($data['css'] ?? ''));
        if ($css === '') {
            throw new RuntimeException('AI 没有返回 CSS');
        }

        $template->update([
            'css' => $css,
            'header_html' => TemplateSanitizer::html((string) ($data['header_html'] ?? '')),
            'footer_html' => TemplateSanitizer::html((string) ($data['footer_html'] ?? '')),
            'status' => SiteTemplate::DRAFT,
            'error' => '',
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        SiteTemplate::query()->where('site_id', $this->siteId)->update([
            'status' => SiteTemplate::FAILED,
            'error' => mb_substr($exception?->getMessage() ?: '生成失败', 0, 1000),
        ]);
    }

    /**
     * 读取参考图（本地上传或 OSS 地址），最大 5MB
     *
     * @return array{mime: string, data: string}|null
     */
    protected function image(string $path): ?array
    {
        if ($path === '') {
            return null;
        }

        if (preg_match('#^https?://#', $path)) {
            $response = Http::timeout(30)->get($path);
            $content = $response->successful() ? $response->body() : '';
        } else {
            $file = storage_path(ltrim($path, '/'));
            $content = is_file($file) ? (string) file_get_contents($file) : '';
        }

        if ($content === '' || strlen($content) > 5 * 1024 * 1024) {
            return null;
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($content) ?: 'image/png';
        if (! in_array($mime, ['image/png', 'image/jpeg', 'image/webp', 'image/gif'], true)) {
            return null;
        }

        return ['mime' => $mime, 'data' => base64_encode($content)];
    }
}
