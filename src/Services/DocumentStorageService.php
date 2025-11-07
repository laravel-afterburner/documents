<?php

namespace Afterburner\Documents\Services;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\DocumentVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentStorageService
{
    /**
     * Store a file for a document.
     *
     * @param  \Illuminate\Http\UploadedFile|string  $file  The uploaded file or path to file
     * @param  \Afterburner\Documents\Models\Document  $document  The document model
     * @param  int|null  $versionNumber  Optional version number for versioned files
     * @return string  The storage path where the file was saved
     */
    public function store($file, Document $document, ?int $versionNumber = null): string
    {
        $disk = $document->storage_disk ?? config('afterburner-documents.r2.bucket') ? 'r2' : 'local';
        $storagePath = $this->generateStoragePath($document, $versionNumber);

        if ($file instanceof UploadedFile) {
            Storage::disk($disk)->putFileAs(
                dirname($storagePath),
                $file,
                basename($storagePath)
            );
        } else {
            // Assume it's a file path
            Storage::disk($disk)->put($storagePath, file_get_contents($file));
        }

        return $storagePath;
    }

    /**
     * Delete a document's file from storage.
     *
     * @param  \Afterburner\Documents\Models\Document  $document  The document model
     * @param  bool  $deleteVersions  Whether to also delete version files
     * @return bool
     */
    public function delete(Document $document, bool $deleteVersions = false): bool
    {
        $disk = $document->storage_disk ?? 'r2';
        $deleted = true;

        // Delete main file
        if (Storage::disk($disk)->exists($document->storage_path)) {
            $deleted = Storage::disk($disk)->delete($document->storage_path);
        }

        // Delete version files if requested
        if ($deleteVersions) {
            foreach ($document->versions as $version) {
                if (Storage::disk($disk)->exists($version->storage_path)) {
                    Storage::disk($disk)->delete($version->storage_path);
                }
            }
        }

        return $deleted;
    }

    /**
     * Get the public URL for a document.
     *
     * @param  \Afterburner\Documents\Models\Document  $document  The document model
     * @return string|null
     */
    public function getUrl(Document $document): ?string
    {
        $disk = $document->storage_disk ?? 'r2';

        if (!Storage::disk($disk)->exists($document->storage_path)) {
            return null;
        }

        return Storage::disk($disk)->url($document->storage_path);
    }

    /**
     * Get a temporary URL for a document.
     *
     * @param  \Afterburner\Documents\Models\Document  $document  The document model
     * @param  int  $expirationMinutes  Minutes until expiration
     * @return string|null
     */
    public function getTemporaryUrl(Document $document, int $expirationMinutes = 60): ?string
    {
        $disk = $document->storage_disk ?? 'r2';

        try {
            return Storage::disk($disk)->temporaryUrl(
                $document->storage_path,
                now()->addMinutes($expirationMinutes)
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the URL for a document version.
     *
     * @param  \Afterburner\Documents\Models\DocumentVersion  $version  The version model
     * @return string|null
     */
    public function getVersionUrl(DocumentVersion $version): ?string
    {
        $disk = $version->document->storage_disk ?? 'r2';

        if (!Storage::disk($disk)->exists($version->storage_path)) {
            return null;
        }

        return Storage::disk($disk)->url($version->storage_path);
    }

    /**
     * Get a temporary URL for a document version.
     *
     * @param  \Afterburner\Documents\Models\DocumentVersion  $version  The version model
     * @param  int  $expirationMinutes  Minutes until expiration
     * @return string|null
     */
    public function getVersionTemporaryUrl(DocumentVersion $version, int $expirationMinutes = 60): ?string
    {
        $disk = $version->document->storage_disk ?? 'r2';

        try {
            return Storage::disk($disk)->temporaryUrl(
                $version->storage_path,
                now()->addMinutes($expirationMinutes)
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Store a chunk of a file upload.
     *
     * @param  string  $uploadId  Unique identifier for the upload session
     * @param  int  $chunkNumber  The chunk number (0-indexed)
     * @param  \Illuminate\Http\UploadedFile  $chunk  The chunk file
     * @return string  Path where the chunk was stored
     */
    public function storeChunk(string $uploadId, int $chunkNumber, UploadedFile $chunk): string
    {
        $disk = config('afterburner-documents.r2.bucket') ? 'r2' : 'local';
        $chunkPath = "chunks/{$uploadId}/{$chunkNumber}";

        Storage::disk($disk)->put($chunkPath, $chunk->getContent());

        return $chunkPath;
    }

    /**
     * Check if a chunk exists.
     *
     * @param  string  $uploadId  Unique identifier for the upload session
     * @param  int  $chunkNumber  The chunk number
     * @return bool
     */
    public function chunkExists(string $uploadId, int $chunkNumber): bool
    {
        $disk = config('afterburner-documents.r2.bucket') ? 'r2' : 'local';
        $chunkPath = "chunks/{$uploadId}/{$chunkNumber}";

        return Storage::disk($disk)->exists($chunkPath);
    }

    /**
     * Get all chunks for an upload session.
     *
     * @param  string  $uploadId  Unique identifier for the upload session
     * @return array  Array of chunk paths
     */
    public function getChunks(string $uploadId): array
    {
        $disk = config('afterburner-documents.r2.bucket') ? 'r2' : 'local';
        $chunkDir = "chunks/{$uploadId}";

        if (!Storage::disk($disk)->exists($chunkDir)) {
            return [];
        }

        $chunks = Storage::disk($disk)->files($chunkDir);
        sort($chunks, SORT_NATURAL);

        return $chunks;
    }

    /**
     * Assemble chunks into a complete file.
     *
     * @param  string  $uploadId  Unique identifier for the upload session
     * @param  string  $finalPath  Where to store the assembled file
     * @param  int  $totalChunks  Total number of chunks expected
     * @return bool  Success status
     */
    public function assembleChunks(string $uploadId, string $finalPath, int $totalChunks): bool
    {
        $disk = config('afterburner-documents.r2.bucket') ? 'r2' : 'local';
        $chunks = $this->getChunks($uploadId);

        // Verify we have all chunks
        if (count($chunks) !== $totalChunks) {
            return false;
        }

        // Create a temporary file to assemble chunks
        $tempFile = tmpfile();
        $tempPath = stream_get_meta_data($tempFile)['uri'];

        foreach ($chunks as $chunkPath) {
            $chunkContent = Storage::disk($disk)->get($chunkPath);
            fwrite($tempFile, $chunkContent);
        }

        // Store the assembled file
        rewind($tempFile);
        Storage::disk($disk)->put($finalPath, stream_get_contents($tempFile));
        fclose($tempFile);

        // Clean up chunks
        $this->cleanupChunks($uploadId);

        return true;
    }

    /**
     * Clean up chunks for an upload session.
     *
     * @param  string  $uploadId  Unique identifier for the upload session
     * @return void
     */
    public function cleanupChunks(string $uploadId): void
    {
        $disk = config('afterburner-documents.r2.bucket') ? 'r2' : 'local';
        $chunkDir = "chunks/{$uploadId}";

        if (Storage::disk($disk)->exists($chunkDir)) {
            Storage::disk($disk)->deleteDirectory($chunkDir);
        }
    }

    /**
     * Generate storage path for a document using config placeholders.
     *
     * @param  \Afterburner\Documents\Models\Document  $document  The document model
     * @param  int|null  $versionNumber  Optional version number
     * @return string
     */
    public function generateStoragePath(Document $document, ?int $versionNumber = null): string
    {
        $pathTemplate = config('afterburner-documents.storage_path', 'documents/{team_id}/{year}/{month}/{document_id}');

        // Use document ID or generate a temporary UUID
        $documentId = $document->id ?? Str::uuid()->toString();

        $replacements = [
            '{team_id}' => $document->team_id,
            '{year}' => now()->year,
            '{month}' => str_pad(now()->month, 2, '0', STR_PAD_LEFT),
            '{document_id}' => $documentId,
        ];

        $path = str_replace(array_keys($replacements), array_values($replacements), $pathTemplate);

        // Add version suffix if provided
        if ($versionNumber !== null) {
            $extension = pathinfo($document->filename ?? 'file', PATHINFO_EXTENSION);
            $basename = pathinfo($document->filename ?? 'file', PATHINFO_FILENAME);
            $path = dirname($path).'/'.$basename.'_v'.$versionNumber.'.'.$extension;
        } else {
            // Ensure filename is included
            if ($document->filename) {
                $path = rtrim($path, '/').'/'.$document->filename;
            } else {
                // Fallback: use document ID as filename
                $path = rtrim($path, '/').'/'.$documentId;
            }
        }

        return $path;
    }
}

