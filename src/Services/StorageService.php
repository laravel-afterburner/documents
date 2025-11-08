<?php

namespace Afterburner\Documents\Services;

use Afterburner\Documents\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageService
{
    /**
     * Get the R2 disk instance.
     * 
     * Note: The 'r2' disk is automatically registered by DocumentsServiceProvider
     * from config/afterburner-documents.php. Ensure your Cloudflare R2 credentials
     * are configured in your .env file.
     */
    protected function getDisk()
    {
        // Try to get r2 disk, fall back to default if not configured
        try {
            return Storage::disk('r2');
        } catch (\Exception $e) {
            // If r2 disk not configured, throw helpful error
            throw new \Exception(
                'R2 disk not configured. Please ensure your Cloudflare R2 credentials are set in '.
                'config/afterburner-documents.php or your .env file. The disk should be automatically '.
                'registered by the DocumentsServiceProvider.'
            );
        }
    }

    /**
     * Store a chunk temporarily.
     *
     * @param  string  $chunkId  Unique identifier for the chunk
     * @param  string  $content  Chunk content
     * @return string  Path where chunk was stored
     */
    public function storeChunk(string $chunkId, string $content): string
    {
        $path = "chunks/{$chunkId}";
        $this->getDisk()->put($path, $content);

        return $path;
    }

    /**
     * Assemble chunks into a complete file.
     *
     * @param  array  $chunkPaths  Array of chunk paths in order
     * @param  string  $destinationPath  Final destination path
     * @return bool
     */
    public function assembleChunks(array $chunkPaths, string $destinationPath): bool
    {
        $disk = $this->getDisk();
        $tempFile = tmpfile();
        $tempPath = stream_get_meta_data($tempFile)['uri'];

        try {
            // Write all chunks to temporary file
            foreach ($chunkPaths as $chunkPath) {
                $chunkContent = $disk->get($chunkPath);
                file_put_contents($tempPath, $chunkContent, FILE_APPEND);
            }

            // Upload assembled file to R2
            $success = $disk->put($destinationPath, file_get_contents($tempPath));

            // Clean up chunks
            foreach ($chunkPaths as $chunkPath) {
                $disk->delete($chunkPath);
            }

            return $success;
        } finally {
            fclose($tempFile);
        }
    }

    /**
     * Store a complete document.
     *
     * @param  string  $content  File content
     * @param  string  $destinationPath  Destination path
     * @return bool
     */
    public function storeDocument(string $content, string $destinationPath): bool
    {
        return $this->getDisk()->put($destinationPath, $content);
    }

    /**
     * Delete a document from storage.
     *
     * @param  string  $path  Storage path
     * @return bool
     */
    public function deleteDocument(string $path): bool
    {
        return $this->getDisk()->delete($path);
    }

    /**
     * Get the URL for a document.
     *
     * @param  string  $path  Storage path
     * @return string
     */
    public function getDocumentUrl(string $path): string
    {
        $disk = $this->getDisk();
        if ($disk->exists($path)) {
            return $disk->url($path);
        }

        return '';
    }

    /**
     * Generate storage path for a document.
     *
     * @param  Document  $document
     * @return string
     */
    public function generateStoragePath(Document $document): string
    {
        $pathTemplate = config('afterburner-documents.storage_path', 'documents/{team_id}/{year}/{month}/{document_id}');
        $now = now();

        $path = Str::replace([
            '{team_id}',
            '{year}',
            '{month}',
            '{document_id}',
        ], [
            $document->team_id,
            $now->year,
            str_pad($now->month, 2, '0', STR_PAD_LEFT),
            $document->id,
        ], $pathTemplate);

        return rtrim($path, '/').'/'.$document->filename;
    }

    /**
     * Generate storage path for a document version.
     *
     * @param  Document  $document
     * @param  int  $versionNumber
     * @return string
     */
    public function generateVersionStoragePath(Document $document, int $versionNumber): string
    {
        $basePath = $this->generateStoragePath($document);
        $baseDir = dirname($basePath);
        $filename = basename($basePath);

        return "{$baseDir}/versions/{$versionNumber}/{$filename}";
    }

    /**
     * Check if a path exists in storage.
     *
     * @param  string  $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return $this->getDisk()->exists($path);
    }

    /**
     * Get file size from storage.
     *
     * @param  string  $path
     * @return int
     */
    public function getSize(string $path): int
    {
        return $this->getDisk()->size($path);
    }
}

