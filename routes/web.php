<?php

use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// ── Public routes ──────────────────────────────────────────────
Route::get('/',       fn() => redirect('/agencies'));
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout',[AuthController::class, 'logout'])->name('logout');

// ── Protected routes (require login) ───────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/agencies',         [AgencyController::class, 'index']);
    Route::get('/agencies/export',  [AgencyController::class, 'exportCsv']);
    Route::post('/agencies/scrape', [AgencyController::class, 'runScraper']);

    // Phase 3 (uncomment when ready):
    // Route::get('/agencies/{agency}',  [AgencyController::class, 'show']);
    // Route::post('/agencies/enrich',   [AgencyController::class, 'enrichEmails']);
});