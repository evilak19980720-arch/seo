<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Front\Http\Controllers\SettingsController;

// 后台接口；前台页面路由在 FrontServiceProvider 里注册
Route::prefix('front')->group(function () {
    Route::get('settings', [SettingsController::class, 'index']);
    Route::post('settings', [SettingsController::class, 'store']);
    // next
});
