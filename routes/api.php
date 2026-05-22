<?php

use App\Http\Controllers\Api\Ai\InternalAiController;
use App\Http\Middleware\AiOrchestrationKeyMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('ai/internal')
    ->name('api.ai.internal.')
    ->middleware(['throttle:ai-internal', AiOrchestrationKeyMiddleware::class])
    ->group(function () {
        Route::get('/health', [InternalAiController::class, 'health'])->name('health');
        Route::get('/openapi.json', [InternalAiController::class, 'openapi'])->name('openapi');
        Route::get('/overview', [InternalAiController::class, 'overview'])->name('overview');
        Route::get('/domains/search', [InternalAiController::class, 'domainsSearch'])->name('domains.search');
        Route::get('/new-entries/search', [InternalAiController::class, 'newEntriesSearch'])->name('new-entries.search');
        Route::get('/storages/search', [InternalAiController::class, 'storagesSearch'])->name('storages.search');
        Route::get('/orders/search', [InternalAiController::class, 'ordersSearch'])->name('orders.search');
        Route::get('/users/search', [InternalAiController::class, 'usersSearch'])->name('users.search');
        Route::get('/lookups', [InternalAiController::class, 'lookups'])->name('lookups');
        Route::get('/analytics/summary', [InternalAiController::class, 'analyticsSummary'])->name('analytics.summary');
        Route::get('/analytics/domain-metrics', [InternalAiController::class, 'domainMetrics'])->name('analytics.domain-metrics');
    });
