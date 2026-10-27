<?php

declare(strict_types=1);

namespace Modules\Content\Http\Controllers;

use Catch\Base\CatchController as Controller;
use Catch\Exceptions\FailedException;
use Illuminate\Http\Request;
use Modules\Content\Models\Setting;

/**
 * @group 内容管理
 *
 * @subgroup 生产配置
 */
class SettingsController extends Controller
{
    public function index(): array
    {
        return ['values' => Setting::values(), 'schema' => Setting::schema()];
    }

    public function store(Request $request): bool
    {
        $values = $request->only(array_keys(Setting::schema()));
        if (isset($values['min_words'], $values['max_words']) && (int) $values['min_words'] >= (int) $values['max_words']) {
            throw new FailedException('最少字数必须小于最多字数');
        }

        return Setting::saveAll($values);
    }
}
