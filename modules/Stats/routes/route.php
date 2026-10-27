<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Permissions\Middlewares\PermissionGate;
use Modules\Stats\Http\Controllers\CrawlsController;
use Modules\Stats\Http\Controllers\DashboardController;
use Modules\Stats\Http\Controllers\OverviewController;

Route::prefix('stats')->group(function () {
    // 首页面板，所有登录用户可见
    Route::get('dashboard', [DashboardController::class, 'index'])->withoutMiddleware(PermissionGate::class);
    Route::get('overview', [OverviewController::class, 'index']);
    Route::get('crawls', [CrawlsController::class, 'index']);
    // next
});
