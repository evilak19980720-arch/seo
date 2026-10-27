<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Stats\Http\Controllers\CrawlsController;
use Modules\Stats\Http\Controllers\OverviewController;

Route::prefix('stats')->group(function () {
    Route::get('overview', [OverviewController::class, 'index']);
    Route::get('crawls', [CrawlsController::class, 'index']);
    // next
});
