<?php

use Afterburner\Documents\Http\Controllers\DocumentsController;
use Afterburner\Documents\Http\Controllers\FilePondUploadController;
use Afterburner\Documents\Models\Document;
use App\Models\Team;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    // Team-based document routes
    Route::get('/teams/{team}/documents', [DocumentsController::class, 'index'])
        ->name('teams.documents.index');

    // Folder navigation route
    Route::get('/teams/{team}/documents/{folder_slug}', [DocumentsController::class, 'folder'])
        ->name('teams.documents.folder')
        ->where('folder_slug', '[a-z0-9-]+');

    // Document download route
    Route::get('/teams/{team}/documents/{document}/download', function (Team $team, Document $document) {
        if (! $document->team->is($team)) {
            abort(404);
        }

        $disk = Storage::disk('r2');
        if (! $disk->exists($document->storage_path)) {
            abort(404, 'Document file not found.');
        }

        return response()->streamDownload(function () use ($disk, $document) {
            echo $disk->get($document->storage_path);
        }, $document->filename, [
            'Content-Type' => $document->mime_type,
        ]);
    })
        ->name('teams.documents.download')
        ->middleware('can:download,document');

    // Inline browser preview (PDF, images, plain text)
    Route::get('/teams/{team}/documents/{document}/preview', function (Team $team, Document $document) {
        if (! $document->team->is($team)) {
            abort(404);
        }

        if (! $document->isPreviewableInBrowser()) {
            abort(404, 'This file cannot be previewed in the browser.');
        }

        $disk = Storage::disk('r2');
        if (! $disk->exists($document->storage_path)) {
            abort(404, 'Document file not found.');
        }

        $filename = str_replace(['"', '\\'], '', $document->filename);

        return response()->stream(function () use ($disk, $document) {
            echo $disk->get($document->storage_path);
        }, 200, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    })
        ->name('teams.documents.preview')
        ->middleware('can:view,document');

    // FilePond native chunked upload routes
    Route::post('/teams/{team}/documents/upload', [FilePondUploadController::class, 'process'])
        ->name('teams.documents.upload.process');

    Route::match(['patch', 'post'], '/teams/{team}/documents/upload/{uploadId}', [FilePondUploadController::class, 'patch'])
        ->name('teams.documents.upload.patch');

    Route::match(['head'], '/teams/{team}/documents/upload/{uploadId}', [FilePondUploadController::class, 'head'])
        ->name('teams.documents.upload.head');

    Route::delete('/teams/{team}/documents/upload/{uploadId}', [FilePondUploadController::class, 'revert'])
        ->name('teams.documents.upload.revert');
});
