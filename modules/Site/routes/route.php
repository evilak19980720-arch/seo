<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Site\Http\Controllers\CloudflareAccountsController;
use Modules\Site\Http\Controllers\DnsTasksController;
use Modules\Site\Http\Controllers\GameProfilesController;
use Modules\Site\Http\Controllers\SitesController;
use Modules\Site\Http\Controllers\SiteTemplatesController;

Route::prefix('site')->group(function () {
    // Cloudflare 账号
    Route::get('cloudflare/accounts/options', [CloudflareAccountsController::class, 'options']);
    Route::post('cloudflare/accounts/{id}/verify', [CloudflareAccountsController::class, 'verify'])->whereNumber('id');
    Route::get('cloudflare/accounts/{id}/zones', [CloudflareAccountsController::class, 'zones'])->whereNumber('id');
    Route::post('cloudflare/accounts/{id}/resolve', [CloudflareAccountsController::class, 'resolve'])->whereNumber('id');
    Route::apiResource('cloudflare/accounts', CloudflareAccountsController::class)->parameters(['accounts' => 'id']);
    Route::put('cloudflare/accounts/enable/{id}', [CloudflareAccountsController::class, 'enable']);

    // 批量解析记录
    Route::get('dns/tasks', [DnsTasksController::class, 'index']);
    Route::post('dns/tasks/{id}/retry', [DnsTasksController::class, 'retry'])->whereNumber('id');
    Route::delete('dns/tasks/{id}', [DnsTasksController::class, 'destroy'])->whereNumber('id');

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
    // 站点模板（按站点 ID）
    Route::get('sites/{id}/template', [SiteTemplatesController::class, 'show'])->whereNumber('id');
    Route::put('sites/{id}/template', [SiteTemplatesController::class, 'update'])->whereNumber('id');
    Route::get('sites/{id}/template/preview', [SiteTemplatesController::class, 'preview'])->whereNumber('id');
    Route::post('sites/{id}/template/generate', [SiteTemplatesController::class, 'generate'])->whereNumber('id');
    Route::post('sites/{id}/template/publish', [SiteTemplatesController::class, 'publish'])->whereNumber('id');
    Route::post('sites/{id}/template/unpublish', [SiteTemplatesController::class, 'unpublish'])->whereNumber('id');
    // next
});
