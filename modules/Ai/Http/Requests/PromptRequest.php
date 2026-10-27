<?php

declare(strict_types=1);

namespace Modules\Ai\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Ai\Enums\PromptType;

class PromptRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'integer', Rule::enum(PromptType::class)],
            'channel_id' => ['required', 'integer', Rule::exists('ai_channels', 'id')->where('deleted_at', 0)],
            'system_prompt' => ['nullable', 'string', 'max:20000'],
            'content' => ['required', 'string', 'max:20000'],
            'status' => ['sometimes', 'integer', 'in:1,2'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => '提示词名称', 'type' => '类型', 'channel_id' => 'AI 渠道', 'system_prompt' => '系统提示词', 'content' => '提示词内容'];
    }
}
