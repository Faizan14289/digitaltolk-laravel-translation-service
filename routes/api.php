<?php

use App\Http\Controllers\Api\TranslationController;
use App\Http\Controllers\Api\ExportController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    // Protected CRUD endpoints with authentication and rate limiting (60 requests/minute)
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::apiResource('translations', TranslationController::class);
    });
    
    // Public export endpoint with rate limiting (30 requests/minute)
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/export/{locale}', [ExportController::class, 'export'])->name('export.translations');
    });
});
