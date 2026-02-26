<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentManager\DocumentManagerController;
use App\Http\Controllers\DocumentManager\DocumentsController;
use App\Http\Controllers\DocumentManager\OwnersController;

Route::get('/document-manager', [DocumentManagerController::class, 'index'])->name('document-manager.index');

Route::prefix('document-manager')->group(function () {
    Route::get('documents', [DocumentsController::class, 'list']);
    Route::get('lookups', [DocumentsController::class, 'lookups']);

    // ✅ ONLY ONE endpoint for owner search
    Route::get('owners/search', [OwnersController::class, 'search'])->name('dm.owners.search');

    Route::post('documents', [DocumentsController::class, 'store']);
    Route::post('documents/{id}', [DocumentsController::class, 'update']);
    Route::post('documents/{id}/delete', [DocumentsController::class, 'destroy']);
});

