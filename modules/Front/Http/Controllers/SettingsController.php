<?php

declare(strict_types=1);

namespace Modules\Front\Http\Controllers;

use Catch\Base\CatchController as Controller;
use Illuminate\Http\Request;
use Modules\Front\Models\FrontSetting;

/**
 * @group 全局配置
 *
 * @subgroup 前台配置
 */
class SettingsController extends Controller
{
    public function index(): array
    {
        return ['values' => FrontSetting::values(), 'schema' => FrontSetting::schema()];
    }

    public function store(Request $request): bool
    {
        $request->validate(['head_code' => ['nullable', 'string', 'max:20000'], 'body_code' => ['nullable', 'string', 'max:20000'], 'footer_text' => ['nullable', 'string', 'max:500']]);

        return FrontSetting::saveAll($request->only(array_keys(FrontSetting::schema())));
    }
}
