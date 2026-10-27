<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Ad\Http\Controllers\AdsController;

Route::prefix('ad')->group(function () {
    Route::get('ads/positions', [AdsController::class, 'positions']);
    Route::apiResource('ads', AdsController::class);
    Route::put('ads/enable/{id}', [AdsController::class, 'enable']);
    // next
});
