<?php

declare(strict_types=1);

namespace Modules\Site\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloudflareAccountRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'account_id' => ['required', 'string', 'alpha_num', 'max:64'],
            // 编辑时留空表示不修改
            'api_token' => [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'integer', 'in:1,2'],
            'remark' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => '账号名称', 'account_id' => 'Account ID', 'api_token' => 'API Token', 'remark' => '备注'];
    }
}
