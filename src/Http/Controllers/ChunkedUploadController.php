<?php

namespace Afterburner\Documents\Http\Controllers;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\DocumentChunk;
use Afterburner\Documents\Services\StorageService;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChunkedUploadController
{
    public function __construct(
        protected StorageService $storageService
    ) {
    }

    /**
     * Upload a single chunk.
     */
    public function uploadChunk(Request $request, Team $team)
    {
        // Ensure user belongs to team
        if (!Auth::user()->belongsToTeam($team)) {
            abort(403, 'Access denied.');
        }

        $request->validate([
            'chunk' => 'required|file|max:'.config('afterburner-documents.upload.chunk_size', 5242880),
            'chunkId' => 'required|string',
            'chunkIndex' => 'nullable|integer|min:0',
            'totalChunks' => 'nullable|integer|min:1',
        ]);

        $chunkId = $request->input('chunkId');
        $chunkIndex = $request->input('chunkIndex', 0);
        $chunkFile = $request->file('chunk');

        // Store chunk in R2
        $chunkPath = $this->storageService->storeChunk($chunkId, file_get_contents($chunkFile->getRealPath()));

        // Track chunk in database
        $documentChunk = DocumentChunk::create([
            'chunk_id' => $chunkId,
            'chunk_index' => $chunkIndex,
            'storage_path' => $chunkPath,
            'size' => $chunkFile->getSize(),
            'expires_at' => now()->addHours(24), // Cleanup after 24 hours if not assembled
        ]);

        return response()->json([
            'success' => true,
            'chunkId' => $chunkId,
            'chunkIndex' => $chunkIndex,
            'message' => 'Chunk uploaded successfully',
        ]);
    }

    /**
     * Assemble chunks into a complete document.
     */
    public function assembleChunks(Request $request, Team $team)
    {
        // Ensure user belongs to team
        if (!Auth::user()->belongsToTeam($team)) {
            abort(403, 'Access denied.');
        }

        $request->validate([
            'chunkIds' => 'required|array',
            'chunkIds.*' => 'required|string',
            'filename' => 'required|string|max:255',
            'mimeType' => 'required|string',
            'totalSize' => 'required|integer|min:1',
            'folderId' => 'nullable|integer|exists:folders,id',
            'notes' => 'nullable|string|max:5000',
        ]);

        $chunkIds = $request->input('chunkIds');
        $filename = $request->input('filename');
        $mimeType = $request->input('mimeType');
        $totalSize = $request->input('totalSize');
        $folderId = $request->input('folderId');
        $notes = $request->input('notes');

        // Verify all chunks exist
        $chunks = DocumentChunk::whereIn('chunk_id', $chunkIds)->get();
        
        if ($chunks->count() !== count($chunkIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Some chunks are missing.',
            ], 400);
        }

        // Sort chunks by index
        $chunks = $chunks->sortBy('chunk_index')->values();

        // Create document record
        $document = DB::transaction(function () use ($team, $folderId, $filename, $mimeType, $totalSize, $notes) {
            return app(\Afterburner\Documents\Actions\UploadDocument::class)->execute(
                $team->id,
                $folderId,
                $filename,
                $mimeType,
                $totalSize,
                Auth::user(),
                false,
                $notes
            );
        });

        // Generate storage path
        $storagePath = $this->storageService->generateStoragePath($document);
        $document->update(['storage_path' => $storagePath]);

        // Assemble chunks
        $chunkPaths = $chunks->pluck('storage_path')->toArray();
        $success = $this->storageService->assembleChunks($chunkPaths, $storagePath);

        if (!$success) {
            $document->delete(); // Cleanup document record
            return response()->json([
                'success' => false,
                'message' => 'Failed to assemble chunks.',
            ], 500);
        }

        // Update chunks with document_id
        DocumentChunk::whereIn('chunk_id', $chunkIds)->update([
            'document_id' => $document->id,
            'expires_at' => null, // No longer need expiration
        ]);

        // Update document status
        $document->update([
            'upload_status' => 'completed',
            'upload_progress' => 100,
        ]);

        // Create initial version
        $document->createVersion($storagePath, $totalSize, Auth::user());

        // Send notification
        Auth::user()->notify(new \Afterburner\Documents\Notifications\DocumentUploadComplete($document));

        return response()->json([
            'success' => true,
            'document' => [
                'id' => $document->id,
                'name' => $document->name,
                'filename' => $document->filename,
            ],
            'message' => 'Document uploaded successfully',
        ]);
    }

    /**
     * Delete a chunk (cleanup).
     */
    public function deleteChunk(Request $request, Team $team, string $chunkId)
    {
        // Ensure user belongs to team
        if (!Auth::user()->belongsToTeam($team)) {
            abort(403, 'Access denied.');
        }

        $chunk = DocumentChunk::where('chunk_id', $chunkId)->first();

        if (!$chunk) {
            return response()->json([
                'success' => false,
                'message' => 'Chunk not found.',
            ], 404);
        }

        // Delete from storage
        $this->storageService->deleteDocument($chunk->storage_path);

        // Delete from database
        $chunk->delete();

        return response()->json([
            'success' => true,
            'message' => 'Chunk deleted successfully',
        ]);
    }
}

