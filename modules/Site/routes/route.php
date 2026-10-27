<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Site\Http\Controllers\CloudflareAccountsController;
use Modules\Site\Http\Controllers\GameProfilesController;
use Modules\Site\Http\Controllers\ServersController;
use Modules\Site\Http\Controllers\SitesController;

Route::prefix('site')->group(function () {
    // 服务器
    Route::get('servers/options', [ServersController::class, 'options']);
    Route::apiResource('servers', ServersController::class);
    Route::put('servers/enable/{id}', [ServersController::class, 'enable']);

    // Cloudflare 账号
    Route::get('cloudflare/accounts/options', [CloudflareAccountsController::class, 'options']);
    Route::post('cloudflare/accounts/{id}/verify', [CloudflareAccountsController::class, 'verify'])->whereNumber('id');
    Route::apiResource('cloudflare/accounts', CloudflareAccountsController::class)->parameters(['accounts' => 'id']);
    Route::put('cloudflare/accounts/enable/{id}', [CloudflareAccountsController::class, 'enable']);

    // 站点
    Route::post('sites/{id}/onboard', [SitesController::class, 'onboard'])->whereNumber('id');
    Route::apiResource('sites', SitesController::class);
    Route::put('sites/enable/{id}', [SitesController::class, 'enable']);
    // 游戏资料（按站点 ID）
    Route::get('sites/{id}/profile', [GameProfilesController::class, 'show'])->whereNumber('id');
    Route::put('sites/{id}/profile', [GameProfilesController::class, 'update'])->whereNumber('id');
    Route::post('sites/{id}/profile/regenerate', [GameProfilesController::class, 'regenerate'])->whereNumber('id');
    Route::post('sites/{id}/profile/confirm', [GameProfilesController::class, 'confirm'])->whereNumber('id');
    Route::post('sites/{id}/profile/unconfirm', [GameProfilesController::class, 'unconfirm'])->whereNumber('id');
    // next
});
