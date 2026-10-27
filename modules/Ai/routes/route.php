<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Ai\Http\Controllers\CallLogsController;
use Modules\Ai\Http\Controllers\ChannelsController;
use Modules\Ai\Http\Controllers\PromptsController;

Route::prefix('ai')->group(function () {
    // 渠道
    Route::get('channels/options', [ChannelsController::class, 'options']);
    Route::post('channels/{id}/test', [ChannelsController::class, 'test'])->whereNumber('id');
    Route::apiResource('channels', ChannelsController::class);
    Route::put('channels/enable/{id}', [ChannelsController::class, 'enable']);

    // 提示词
    Route::get('prompts/types', [PromptsController::class, 'types']);
    Route::apiResource('prompts', PromptsController::class);
    Route::put('prompts/enable/{id}', [PromptsController::class, 'enable']);

    // 调用日志
    Route::get('logs/summary', [CallLogsController::class, 'summary']);
    Route::get('logs', [CallLogsController::class, 'index']);
    // next
});
