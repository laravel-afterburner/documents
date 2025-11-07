<?php

namespace Afterburner\Documents\Actions;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Services\DocumentStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class HandleChunkedUpload
{
    public function __construct(
        protected DocumentStorageService $storageService
    ) {
    }

    /**
     * Initiate a chunked upload session.
     *
     * @param  string  $filename  Original filename
     * @param  int  $totalChunks  Total number of chunks expected
     * @param  int  $totalSize  Total file size in bytes
     * @return array  Upload session data
     */
    public function initiate(string $filename, int $totalChunks, int $totalSize): array
    {
        $uploadId = Str::uuid()->toString();

        Cache::put("upload:{$uploadId}", [
            'filename' => $filename,
            'total_chunks' => $totalChunks,
            'total_size' => $totalSize,
            'uploaded_chunks' => [],
            'created_at' => now(),
        ], now()->addHours(24));

        return [
            'upload_id' => $uploadId,
            'chunk_size' => config('afterburner-documents.upload.chunk_size', 5242880),
        ];
    }

    /**
     * Upload a single chunk.
     *
     * @param  string  $uploadId  Upload session ID
     * @param  int  $chunkNumber  Chunk number (0-indexed)
     * @param  \Illuminate\Http\UploadedFile  $chunk  The chunk file
     * @return array  Upload status
     */
    public function uploadChunk(string $uploadId, int $chunkNumber, UploadedFile $chunk): array
    {
        $session = Cache::get("upload:{$uploadId}");

        if (!$session) {
            return [
                'success' => false,
                'error' => 'Upload session not found or expired',
            ];
        }

        // Validate chunk size
        $maxChunkSize = config('afterburner-documents.upload.chunk_size', 5242880);
        if ($chunk->getSize() > $maxChunkSize) {
            return [
                'success' => false,
                'error' => 'Chunk size exceeds maximum allowed size',
            ];
        }

        // Store chunk
        $this->storageService->storeChunk($uploadId, $chunkNumber, $chunk);

        // Update session
        $session['uploaded_chunks'][] = $chunkNumber;
        $session['uploaded_chunks'] = array_unique($session['uploaded_chunks']);
        sort($session['uploaded_chunks']);

        Cache::put("upload:{$uploadId}", $session, now()->addHours(24));

        $allChunksUploaded = count($session['uploaded_chunks']) === $session['total_chunks'];

        return [
            'success' => true,
            'chunk_number' => $chunkNumber,
            'uploaded_chunks' => count($session['uploaded_chunks']),
            'total_chunks' => $session['total_chunks'],
            'complete' => $allChunksUploaded,
        ];
    }

    /**
     * Complete the chunked upload and assemble the file.
     *
     * @param  string  $uploadId  Upload session ID
     * @param  string  $finalPath  Where to store the final file
     * @return array  Result with file path or error
     */
    public function complete(string $uploadId, string $finalPath): array
    {
        $session = Cache::get("upload:{$uploadId}");

        if (!$session) {
            return [
                'success' => false,
                'error' => 'Upload session not found or expired',
            ];
        }

        // Verify all chunks are uploaded
        if (count($session['uploaded_chunks']) !== $session['total_chunks']) {
            return [
                'success' => false,
                'error' => 'Not all chunks have been uploaded',
                'uploaded' => count($session['uploaded_chunks']),
                'expected' => $session['total_chunks'],
            ];
        }

        // Assemble chunks
        $success = $this->storageService->assembleChunks(
            $uploadId,
            $finalPath,
            $session['total_chunks']
        );

        if (!$success) {
            return [
                'success' => false,
                'error' => 'Failed to assemble chunks',
            ];
        }

        // Clean up session
        Cache::forget("upload:{$uploadId}");

        return [
            'success' => true,
            'path' => $finalPath,
            'filename' => $session['filename'],
            'size' => $session['total_size'],
        ];
    }

    /**
     * Cancel an upload session and clean up chunks.
     *
     * @param  string  $uploadId  Upload session ID
     * @return bool
     */
    public function cancel(string $uploadId): bool
    {
        $session = Cache::get("upload:{$uploadId}");

        if (!$session) {
            return false;
        }

        $this->storageService->cleanupChunks($uploadId);
        Cache::forget("upload:{$uploadId}");

        return true;
    }

    /**
     * Get upload session status.
     *
     * @param  string  $uploadId  Upload session ID
     * @return array|null
     */
    public function getStatus(string $uploadId): ?array
    {
        $session = Cache::get("upload:{$uploadId}");

        if (!$session) {
            return null;
        }

        return [
            'upload_id' => $uploadId,
            'filename' => $session['filename'],
            'total_chunks' => $session['total_chunks'],
            'uploaded_chunks' => count($session['uploaded_chunks']),
            'total_size' => $session['total_size'],
            'progress' => round((count($session['uploaded_chunks']) / $session['total_chunks']) * 100, 2),
            'complete' => count($session['uploaded_chunks']) === $session['total_chunks'],
        ];
    }
}

