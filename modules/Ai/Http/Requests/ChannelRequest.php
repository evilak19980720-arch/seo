<?php

declare(strict_types=1);

namespace Modules\Ai\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Ai\Enums\Provider;

class ChannelRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'provider' => ['required', 'integer', Rule::enum(Provider::class)],
            'base_url' => ['required', 'url', 'max:255'],
            // 编辑时留空表示不修改
            'api_key' => [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'max:500'],
            'model' => ['required', 'string', 'max:100'],
            'temperature' => ['sometimes', 'numeric', 'min:0', 'max:2'],
            'max_tokens' => ['sometimes', 'integer', 'min:256', 'max:200000'],
            'timeout' => ['sometimes', 'integer', 'min:10', 'max:600'],
            'status' => ['sometimes', 'integer', 'in:1,2'],
            'remark' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => '渠道名称', 'provider' => '渠道协议', 'base_url' => '接口地址', 'api_key' => 'API Key', 'model' => '模型'];
    }
}
