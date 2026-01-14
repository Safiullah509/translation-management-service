<?php 

use App\Http\Controllers\Api\TranslationController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/translations', [TranslationController::class, 'index']);
    Route::get('/translations/{translation}', [TranslationController::class, 'show']);
    Route::post('/translations', [TranslationController::class, 'store']);
    Route::put('/translations/{translation}', [TranslationController::class, 'update']);
    Route::delete('/translations/{translation}', [TranslationController::class, 'destroy']);
    Route::get('/translations/search', [TranslationController::class, 'search']);
});

Route::get('/export/{locale}', [
    \App\Http\Controllers\Api\TranslationExportController::class,
    'export'
]);