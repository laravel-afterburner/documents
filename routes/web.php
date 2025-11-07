<?php

use Afterburner\Documents\Http\Controllers\DocumentController;
use Afterburner\Documents\Http\Controllers\DocumentPermissionController;
use Afterburner\Documents\Http\Controllers\DocumentVersionController;
use Afterburner\Documents\Http\Controllers\FolderController;
use Afterburner\Documents\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Document Management Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the DocumentsServiceProvider. They handle
| all document management functionality including uploads, viewing,
| version control, and permissions.
|
*/

Route::middleware(['web', 'auth'])->group(function () {
    // Document Routes
    Route::prefix('teams/{team}/documents')->name('documents.')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::get('/{document}', [DocumentController::class, 'show'])->name('show');
        Route::put('/{document}', [DocumentController::class, 'update'])->name('update');
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
        Route::get('/{document}/download', [DocumentController::class, 'download'])->name('download');
        Route::get('/{document}/preview', [DocumentController::class, 'preview'])->name('preview');

        // Document Versions
        Route::prefix('{document}/versions')->name('versions.')->group(function () {
            Route::get('/', [DocumentVersionController::class, 'index'])->name('index');
            Route::get('/{version}', [DocumentVersionController::class, 'show'])->name('show');
            Route::post('/{version}/restore', [DocumentVersionController::class, 'restore'])->name('restore');
            Route::get('/{version}/download', [DocumentVersionController::class, 'download'])->name('download');
        });

        // Document Permissions
        Route::prefix('{document}/permissions')->name('permissions.')->group(function () {
            Route::get('/', [DocumentPermissionController::class, 'index'])->name('index');
            Route::post('/', [DocumentPermissionController::class, 'store'])->name('store');
            Route::put('/{permission}', [DocumentPermissionController::class, 'update'])->name('update');
            Route::delete('/{permission}', [DocumentPermissionController::class, 'destroy'])->name('destroy');
        });
    });

    // Folder Routes
    Route::prefix('teams/{team}/folders')->name('folders.')->group(function () {
        Route::get('/', [FolderController::class, 'index'])->name('index');
        Route::post('/', [FolderController::class, 'store'])->name('store');
        Route::get('/{folder}', [FolderController::class, 'show'])->name('show');
        Route::put('/{folder}', [FolderController::class, 'update'])->name('update');
        Route::delete('/{folder}', [FolderController::class, 'destroy'])->name('destroy');
    });

    // Upload Routes
    Route::prefix('upload')->name('upload.')->group(function () {
        Route::post('/initiate', [UploadController::class, 'initiate'])->name('initiate');
        Route::post('/chunk', [UploadController::class, 'uploadChunk'])->name('chunk');
        Route::post('/complete', [UploadController::class, 'complete'])->name('complete');
        Route::post('/cancel', [UploadController::class, 'cancel'])->name('cancel');
        Route::get('/{uploadId}/status', [UploadController::class, 'status'])->name('status');
    });
});

