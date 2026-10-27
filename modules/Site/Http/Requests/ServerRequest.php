<?php

declare(strict_types=1);

namespace Modules\Site\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServerRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'ip' => ['required', 'ip', Rule::unique('site_servers')->where('deleted_at', 0)->ignore($this->route('server'))],
            'region' => ['nullable', 'string', 'max:50'],
            'capacity' => ['required', 'integer', 'min:1', 'max:10000'],
            'status' => ['sometimes', 'integer', 'in:1,2'],
            'remark' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => '服务器名称', 'ip' => 'IP', 'region' => '区域', 'capacity' => '容量', 'remark' => '备注'];
    }
}
