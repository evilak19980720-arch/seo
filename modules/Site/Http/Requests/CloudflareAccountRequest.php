<?php

declare(strict_types=1);

namespace Modules\Site\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloudflareAccountRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    protected function prepareForValidation(): void
    {
        // 复制时常带上空格、换行，Cloudflare 会直接判为无效 Token
        if (is_string($this->input('api_token'))) {
            $this->merge(['api_token' => preg_replace('/\s+/', '', $this->input('api_token'))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'account_id' => ['required', 'string', 'alpha_num', 'max:64'],
            // 编辑时留空表示不修改
            // Global API Key 是 37 位十六进制，不能用 Bearer 方式调用
            'api_token' => [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'max:255', 'not_regex:/^[0-9a-f]{37}$/i'],
            'status' => ['sometimes', 'integer', 'in:1,2'],
            'remark' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return ['api_token.not_regex' => '这是 Global API Key，请在 Cloudflare「API 令牌」里创建自定义令牌后填写'];
    }

    public function attributes(): array
    {
        return ['name' => '账号名称', 'account_id' => 'Account ID', 'api_token' => 'API Token', 'remark' => '备注'];
    }
}
