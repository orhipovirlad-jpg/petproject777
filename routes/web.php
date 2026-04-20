<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CabinetController;
use App\Http\Controllers\MarginCalculatorController;
use App\Http\Controllers\MarginSnapshotController;
use App\Http\Controllers\ProSubscriptionController;
use App\Http\Controllers\WorkbookController;

Route::view('/', 'landing')->name('home');
Route::get('/cabinet/calculator', static function (): \Illuminate\Http\RedirectResponse {
    return redirect()->route('workbook.products');
})
    ->middleware('cabinet.auth')
    ->name('mvp.index');
Route::post('/calculate', [MarginCalculatorController::class, 'calculate'])
    ->middleware('cabinet.auth')
    ->name('mvp.calculate');
Route::post('/calculate-ajax', [MarginCalculatorController::class, 'calculateAjax'])
    ->middleware('cabinet.auth')
    ->name('mvp.calculate-ajax');
Route::post('/platform-compare', [MarginCalculatorController::class, 'comparePlatforms'])
    ->middleware(['cabinet.auth', 'throttle:30,1'])
    ->name('mvp.platform-compare');
Route::post('/export-csv', [MarginCalculatorController::class, 'exportCsv'])
    ->middleware('cabinet.auth')
    ->name('mvp.export-csv');
Route::post('/ai-insight', [MarginCalculatorController::class, 'generateAiInsight'])
    ->middleware('cabinet.auth')
    ->name('mvp.ai-insight');
Route::post('/ai-insight-ajax', [MarginCalculatorController::class, 'generateAiInsightAjax'])
    ->middleware('cabinet.auth')
    ->name('mvp.ai-insight-ajax');
Route::post('/ai-launch-plan', [MarginCalculatorController::class, 'generateAiLaunchPlan'])
    ->middleware('cabinet.auth')
    ->name('mvp.ai-launch-plan');
Route::post('/ai-launch-plan-ajax', [MarginCalculatorController::class, 'generateAiLaunchPlanAjax'])
    ->middleware('cabinet.auth')
    ->name('mvp.ai-launch-plan-ajax');
Route::get('/margin-snapshots', [MarginSnapshotController::class, 'index'])
    ->middleware('cabinet.auth')
    ->name('mvp.snapshots');
Route::post('/margin-snapshots', [MarginSnapshotController::class, 'store'])
    ->middleware('cabinet.auth')
    ->name('mvp.snapshots.store');
Route::delete('/margin-snapshots/{snapshot}', [MarginSnapshotController::class, 'destroy'])
    ->middleware('cabinet.auth')
    ->name('mvp.snapshots.destroy');
Route::get('/pro', [ProSubscriptionController::class, 'show'])->name('pro.show');
Route::post('/pro/checkout', [ProSubscriptionController::class, 'create'])->name('pro.checkout');
Route::get('/pro/return', [ProSubscriptionController::class, 'handleReturn'])->name('pro.return');

Route::get('/cabinet', [CabinetController::class, 'show'])->name('cabinet.show');
Route::get('/cabinet/login', [CabinetController::class, 'showLogin'])->name('cabinet.login-page');
Route::get('/cabinet/register', [CabinetController::class, 'showRegister'])->name('cabinet.register-page');
Route::post('/cabinet/register', [CabinetController::class, 'register'])
    ->middleware('throttle:12,60')
    ->name('cabinet.register');
Route::post('/cabinet/login', [CabinetController::class, 'login'])
    ->middleware('throttle:30,1')
    ->name('cabinet.login');
Route::post('/cabinet/logout', [CabinetController::class, 'logout'])->name('cabinet.logout');

Route::middleware('cabinet.auth')->prefix('workbook')->name('workbook.')->group(function (): void {
    Route::get('/guide', [WorkbookController::class, 'guide'])->name('guide');
    Route::get('/products', [WorkbookController::class, 'products'])->name('products');
    Route::post('/products', [WorkbookController::class, 'storeProduct'])->name('products.store');
    Route::post('/products/{index}/delete', [WorkbookController::class, 'deleteProduct'])->name('products.delete');

    Route::get('/wb-fbw', [WorkbookController::class, 'wbFbw'])->name('wb-fbw');
    Route::get('/wb-fbs', [WorkbookController::class, 'wbFbs'])->name('wb-fbs');
    Route::get('/ozon-fbo', [WorkbookController::class, 'ozonFbo'])->name('ozon-fbo');
    Route::get('/ozon-fbs', [WorkbookController::class, 'ozonFbs'])->name('ozon-fbs');
    Route::post('/settings/{model}', [WorkbookController::class, 'updateModelSettings'])->name('settings.update');

    Route::get('/compare', [WorkbookController::class, 'compare'])->name('compare');
    Route::get('/dashboard-models', [WorkbookController::class, 'dashboardModels'])->name('dashboard-models');
    Route::get('/dashboard-top', [WorkbookController::class, 'dashboardTop'])->name('dashboard-top');
    Route::get('/dashboard', [WorkbookController::class, 'dashboard'])->name('dashboard');
    Route::get('/autopilot', [WorkbookController::class, 'autopilot'])->name('autopilot');
});
