<?php

declare(strict_types=1);

namespace Modules\Content\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'site_id' => [$creating ? 'required' : 'sometimes', 'integer', Rule::exists('sites', 'id')->where('deleted_at', 0)],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:191'],
            'keyword' => ['nullable', 'string', 'max:191'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'cover' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'site_id' => '站点', 'title' => '标题', 'slug' => 'URL 路径', 'keyword' => '关键词',
            'meta_title' => 'SEO 标题', 'meta_description' => 'SEO 描述', 'cover' => '封面', 'content' => '正文',
        ];
    }
}
