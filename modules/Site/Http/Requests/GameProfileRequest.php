<?php

declare(strict_types=1);

namespace Modules\Site\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GameProfileRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'genre' => ['nullable', 'string', 'max:100'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'gameplay' => ['nullable', 'string', 'max:5000'],
            'features' => ['nullable', 'array', 'max:20'],
            'features.*' => ['string', 'max:300'],
            'faq' => ['nullable', 'array', 'max:30'],
            'faq.*.q' => ['required', 'string', 'max:300'],
            'faq.*.a' => ['required', 'string', 'max:2000'],
            'store_links' => ['nullable', 'array'],
            'store_links.google_play' => ['nullable', 'url', 'max:500'],
            'store_links.app_store' => ['nullable', 'url', 'max:500'],
            'store_links.web' => ['nullable', 'url', 'max:500'],
            'screenshots' => ['nullable', 'array', 'max:20'],
            'screenshots.*' => ['string', 'max:500'],
            'video_url' => ['nullable', 'url', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'genre' => '游戏类型', 'summary' => '简介', 'gameplay' => '玩法', 'features' => '特色', 'faq' => '常见问题',
            'store_links.google_play' => 'Google Play 链接', 'store_links.app_store' => 'App Store 链接', 'store_links.web' => '网页版链接',
            'screenshots' => '截图', 'video_url' => '视频地址',
        ];
    }
}
