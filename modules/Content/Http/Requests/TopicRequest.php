<?php

declare(strict_types=1);

namespace Modules\Content\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TopicRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'site_id' => [$creating ? 'required' : 'sometimes', 'integer', Rule::exists('sites', 'id')->where('deleted_at', 0)],
            'title' => ['required', 'string', 'max:255'],
            'keyword' => ['nullable', 'string', 'max:191'],
            'intent' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'integer', 'in:1,4'],
        ];
    }

    public function attributes(): array
    {
        return ['site_id' => '站点', 'title' => '选题', 'keyword' => '关键词', 'intent' => '搜索意图', 'status' => '状态'];
    }
}
