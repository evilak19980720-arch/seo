<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Content\Http\Controllers\ArticlesController;
use Modules\Content\Http\Controllers\SettingsController;
use Modules\Content\Http\Controllers\TopicsController;

Route::prefix('content')->group(function () {
    // 选题
    Route::post('topics/generate', [TopicsController::class, 'generate']);
    Route::post('topics/{id}/write', [TopicsController::class, 'write'])->whereNumber('id');
    Route::apiResource('topics', TopicsController::class);

    // 文章
    Route::post('articles/{id}/publish', [ArticlesController::class, 'publish'])->whereNumber('id');
    Route::post('articles/{id}/unpublish', [ArticlesController::class, 'unpublish'])->whereNumber('id');
    Route::post('articles/{id}/rewrite', [ArticlesController::class, 'rewrite'])->whereNumber('id');
    Route::apiResource('articles', ArticlesController::class);

    // 生产配置
    Route::get('settings', [SettingsController::class, 'index']);
    Route::post('settings', [SettingsController::class, 'store']);
    // next
});
