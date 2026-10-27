<?php

declare(strict_types=1);

namespace Modules\Site\Http\Requests;

use Catch\Enums\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Site\Models\Site;

class SiteRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    protected function prepareForValidation(): void
    {
        if ($this->filled('domain')) {
            $this->merge(['domain' => Site::normalizeDomain((string) $this->input('domain'))]);
        }
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'name' => ['required', 'string', 'max:100'],
            'domain' => [
                $creating ? 'required' : 'sometimes',
                'string',
                'max:191',
                'regex:/^(?!-)[a-z0-9-]{1,63}(?<!-)(\.(?!-)[a-z0-9-]{1,63}(?<!-))+$/',
                Rule::unique('sites')->where('deleted_at', 0)->ignore($this->route('site')),
            ],
            'cloudflare_account_id' => [
                $creating ? 'required' : 'sometimes',
                'integer',
                Rule::exists('site_cloudflare_accounts', 'id')->where('status', Status::Enable->value)->where('deleted_at', 0),
            ],
            'ip' => ['nullable', 'ip'],
            'locale' => ['required', 'string', 'max:10'],
            'title' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:500'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'string', 'max:500'],
            'game_brief' => ['nullable', 'string', 'max:1000'],
            'reference_image' => ['nullable', 'string', 'max:500'],
            'style_notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'integer', 'in:1,2'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '网站名称', 'domain' => '域名', 'cloudflare_account_id' => 'Cloudflare 账号', 'ip' => '解析 IP',
            'locale' => '语言', 'title' => '标题', 'description' => '描述', 'keywords' => '关键词', 'logo' => 'Logo', 'game_brief' => '游戏说明', 'reference_image' => '模板参考图', 'style_notes' => '风格说明',
        ];
    }

    public function messages(): array
    {
        return ['domain.regex' => '域名格式不正确，例如 example.com.br'];
    }
}
