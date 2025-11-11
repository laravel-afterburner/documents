<?php

use Afterburner\Documents\Http\Controllers\ChunkedUploadController;
use Afterburner\Documents\Http\Controllers\DocumentsController;
use Afterburner\Documents\Models\Document;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    // Team-based document routes
    Route::get('/teams/{team}/documents', [DocumentsController::class, 'index'])
        ->name('teams.documents.index');
    
    // Folder navigation route
    Route::get('/teams/{team}/documents/{folder_slug}', [DocumentsController::class, 'folder'])
        ->name('teams.documents.folder')
        ->where('folder_slug', '[a-z0-9-]+');

    // Document download route
    Route::get('/teams/{team}/documents/{document}/download', function (\App\Models\Team $team, Document $document) {
        if (!$document->team->is($team)) {
            abort(404);
        }
        
        $disk = \Illuminate\Support\Facades\Storage::disk('r2');
        if (!$disk->exists($document->storage_path)) {
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

    // Chunked upload routes
    Route::post('/teams/{team}/documents/chunks', [ChunkedUploadController::class, 'uploadChunk'])
        ->name('teams.documents.chunks.upload');
    
    Route::post('/teams/{team}/documents/chunks/assemble', [ChunkedUploadController::class, 'assembleChunks'])
        ->name('teams.documents.chunks.assemble');
    
    Route::delete('/teams/{team}/documents/chunks/{chunkId}', [ChunkedUploadController::class, 'deleteChunk'])
        ->name('teams.documents.chunks.delete');
});

