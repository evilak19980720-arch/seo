<?php

declare(strict_types=1);

namespace Modules\Ad\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Ad\Enums\AdPosition;
use Modules\Ad\Enums\AdType;

class AdRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        $image = (int) $this->input('type') === AdType::Image->value;

        return [
            'name' => ['required', 'string', 'max:100'],
            'position' => ['required', Rule::enum(AdPosition::class)],
            'type' => ['required', Rule::enum(AdType::class)],
            'image' => [$image ? 'required' : 'nullable', 'string', 'max:500'],
            'width' => ['nullable', 'integer', 'min:0', 'max:4000'],
            'height' => ['nullable', 'integer', 'min:0', 'max:4000'],
            'link' => [$image ? 'required' : 'nullable', 'url', 'max:500'],
            'alt' => ['nullable', 'string', 'max:191'],
            'html' => [$image ? 'nullable' : 'required', 'string', 'max:20000'],
            'site_ids' => ['nullable', 'array'],
            'site_ids.*' => ['integer'],
            'weight' => ['nullable', 'integer', 'min:1', 'max:100'],
            'start_at' => ['nullable', 'integer', 'min:0'],
            'end_at' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', 'integer', 'in:1,2'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '广告名称', 'position' => '广告位', 'type' => '类型', 'image' => '图片', 'link' => '跳转地址',
            'html' => '广告代码', 'site_ids' => '投放站点', 'weight' => '权重', 'start_at' => '开始时间', 'end_at' => '结束时间',
        ];
    }
}
