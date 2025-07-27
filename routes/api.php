<?php

use App\Http\Controllers\Api\TranslationController;
use App\Http\Controllers\Api\ExportController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->group(function () { // Secure CRUD endpoints
        Route::apiResource('translations', TranslationController::class);
    });
    Route::get('/export/{locale}', [ExportController::class, 'export'])->name('export.translations'); // Public export
});
