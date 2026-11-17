<?php

use App\Http\Controllers\Api\V1\AreaController;
use App\Http\Controllers\Api\V1\AssetCategoryController;
use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\AssetLocationController;
use App\Http\Controllers\Api\V1\AssetStatusController;
use App\Http\Controllers\Api\V1\AuditEventController;
use App\Http\Controllers\Api\V1\Authentication\LoginChallengeController;
use App\Http\Controllers\Api\V1\Authentication\NewPasswordController;
use App\Http\Controllers\Api\V1\Authentication\PasswordResetLinkController;
use App\Http\Controllers\Api\V1\Authentication\SessionController;
use App\Http\Controllers\Api\V1\CurrentUserController;
use App\Http\Controllers\Api\V1\CustodianshipController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DisposalRequestController;
use App\Http\Controllers\Api\V1\InventoryCampaignController;
use App\Http\Controllers\Api\V1\MovementRequestController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('/auth/login', [SessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('auth.login');
    Route::post('/auth/mfa', [LoginChallengeController::class, 'store'])
        ->middleware('throttle:mfa')
        ->name('auth.mfa');
    Route::post('/auth/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('auth.password.email');
    Route::post('/auth/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('auth.password.reset');

    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::get('/auth/me', [CurrentUserController::class, 'show'])->name('auth.me');
        Route::post('/auth/logout', [SessionController::class, 'destroy'])->name('auth.logout');
        Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard.show');

        Route::apiResource('users', UserController::class)->only(['index', 'store', 'show', 'update']);
        Route::apiResource('areas', AreaController::class)->only(['index', 'store', 'show', 'update']);
        Route::apiResource('assets', AssetController::class)->only(['index', 'store', 'show', 'update']);
        Route::get('/assets/code/{assetCode}', [AssetController::class, 'byCode'])->name('assets.by-code');
        Route::get('/assets/{asset}/custodianships', [CustodianshipController::class, 'index'])->name('assets.custodianships.index');
        Route::post('/assets/{asset}/custodianships', [CustodianshipController::class, 'store'])->name('assets.custodianships.store');
        Route::get('/assets/{asset}/documents', [AssetController::class, 'documents'])->name('assets.documents.index');
        Route::post('/assets/{asset}/documents', [AssetController::class, 'uploadDocument'])->name('assets.documents.store');
        Route::get('/assets/{asset}/documents/{document}', [AssetController::class, 'showDocument'])->name('assets.documents.show');
        Route::apiResource('asset-categories', AssetCategoryController::class)->only(['index', 'store', 'show', 'update']);
        Route::get('/asset-statuses', [AssetStatusController::class, 'index'])->name('asset-statuses.index');
        Route::apiResource('asset-locations', AssetLocationController::class)->only(['index', 'store', 'show', 'update']);
        Route::apiResource('roles', RoleController::class)->only(['index', 'show']);
        Route::apiResource('audit-events', AuditEventController::class)->only(['index', 'show']);
        Route::post('/audit-events/{audit_event}/archive', [AuditEventController::class, 'archive'])->name('audit-events.archive');
        Route::apiResource('movement-requests', MovementRequestController::class)->only(['index', 'store', 'show']);
        Route::post('/movement-requests/{movement_request}/decide', [MovementRequestController::class, 'decide'])->name('movement-requests.decide');
        Route::post('/movement-requests/{movement_request}/receive', [MovementRequestController::class, 'receive'])->name('movement-requests.receive');
        Route::apiResource('inventory-campaigns', InventoryCampaignController::class)->only(['index', 'store', 'show']);
        Route::get('/inventory-campaigns/{inventory_campaign}/checks', [InventoryCampaignController::class, 'checks'])->name('inventory-campaigns.checks.index');
        Route::post('/inventory-campaigns/{inventory_campaign}/checks', [InventoryCampaignController::class, 'check'])->name('inventory-campaigns.checks.store');
        Route::post('/inventory-campaigns/{inventory_campaign}/start', [InventoryCampaignController::class, 'start'])->name('inventory-campaigns.start');
        Route::post('/inventory-campaigns/{inventory_campaign}/close', [InventoryCampaignController::class, 'close'])->name('inventory-campaigns.close');
        Route::get('/disposal-requests', [DisposalRequestController::class, 'index'])->name('disposal-requests.index');
        Route::post('/assets/{asset}/disposal-requests', [DisposalRequestController::class, 'store'])->name('assets.disposal-requests.store');
        Route::post('/disposal-requests/{disposal_request}/decide', [DisposalRequestController::class, 'decide'])->name('disposal-requests.decide');
        Route::get('/reports/assets.csv', [ReportController::class, 'assets'])->name('reports.assets');
    });
});
