<?php

namespace Afterburner\Documents\Services;

use Afterburner\Documents\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageService
{
    protected function getDisk()
    {
        try {
            return Storage::disk('r2');
        } catch (\Exception $e) {
            throw new \Exception(
                'R2 disk not configured. Please ensure your Cloudflare R2 credentials are set in '.
                'config/afterburner-documents.php or your .env file. The disk should be automatically '.
                'registered by the DocumentsServiceProvider.'
            );
        }
    }

    protected function getUploadSessionDisk()
    {
        return Storage::disk('documents-uploads');
    }

    public function createUploadSessionPart(string $uploadId): string
    {
        $path = "sessions/{$uploadId}.part";
        $this->getUploadSessionDisk()->put($path, '');

        return $path;
    }

    public function stageFileAtUploadSessionPart(string $sourcePath, ?string $uploadId = null): string
    {
        $uploadId ??= (string) Str::uuid();
        $partPath = $this->createUploadSessionPart($uploadId);
        $destination = $this->getUploadSessionDisk()->path($partPath);

        if (! copy($sourcePath, $destination)) {
            $this->deleteUploadSessionPart($partPath);

            throw new \RuntimeException('Unable to stage uploaded file for cloud transfer.');
        }

        return $partPath;
    }

    public function appendToUploadSessionPart(string $partPath, int $offset, string $data): int
    {
        $absolutePath = $this->getUploadSessionDisk()->path($partPath);

        $handle = fopen($absolutePath, 'c+b');

        if ($handle === false) {
            throw new \RuntimeException('Unable to open upload session file for writing.');
        }

        try {
            if (fseek($handle, $offset) !== 0) {
                throw new \RuntimeException('Unable to seek upload session file.');
            }

            $written = fwrite($handle, $data);

            if ($written === false) {
                throw new \RuntimeException('Unable to write upload chunk.');
            }

            fflush($handle);

            return $offset + $written;
        } finally {
            fclose($handle);
        }
    }

    public function finalizeUploadSessionPart(string $partPath, string $destinationPath): bool
    {
        $sessionDisk = $this->getUploadSessionDisk();
        $sourcePath = $sessionDisk->path($partPath);
        $stream = fopen($sourcePath, 'r');

        if ($stream === false) {
            return false;
        }

        try {
            $success = $this->getDisk()->writeStream($destinationPath, $stream) !== false;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if ($success) {
            $sessionDisk->delete($partPath);
        }

        return $success;
    }

    public function deleteUploadSessionPart(string $partPath): bool
    {
        return $this->getUploadSessionDisk()->delete($partPath);
    }

    public function storeDocument(string $content, string $destinationPath): bool
    {
        return $this->getDisk()->put($destinationPath, $content) !== false;
    }

    public function storeDocumentFromPath(string $sourcePath, string $destinationPath): bool
    {
        $stream = fopen($sourcePath, 'r');

        if ($stream === false) {
            return false;
        }

        try {
            return $this->getDisk()->writeStream($destinationPath, $stream) !== false;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function storageFailureMessage(): string
    {
        $diskConfig = config('filesystems.disks.r2', []);
        $driver = $diskConfig['driver'] ?? 's3';

        if ($driver === 's3' && empty($diskConfig['bucket'] ?? null)) {
            return 'Cloudflare R2 is not configured. Set AFTERBURNER_DOCUMENTS_R2_* values in your .env file.';
        }

        return 'The storage request was rejected. Check your storage credentials and bucket permissions.';
    }

    public function deleteDocument(string $path): bool
    {
        return $this->getDisk()->delete($path);
    }

    public function deleteDocumentStorage(Document $document): void
    {
        $disk = $this->getDisk();

        if ($document->storage_path !== '' && $disk->exists($document->storage_path)) {
            $disk->delete($document->storage_path);
        }

        foreach ($document->versions as $version) {
            if ($version->storage_path !== '' && $disk->exists($version->storage_path)) {
                $disk->delete($version->storage_path);
            }
        }
    }

    public function getDocumentUrl(string $path): string
    {
        $disk = $this->getDisk();
        if ($disk->exists($path)) {
            return $disk->url($path);
        }

        return '';
    }

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

        return rtrim($path, '/').'/'.static::safeStorageFilename($document->filename);
    }

    /**
     * Build an object-storage-safe filename while preserving the extension.
     */
    public static function safeStorageFilename(string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        $safeBasename = Str::slug($basename);

        if ($safeBasename === '') {
            $safeBasename = 'file';
        }

        return $extension !== ''
            ? "{$safeBasename}.{$extension}"
            : $safeBasename;
    }

    public function copy(string $from, string $to): bool
    {
        $disk = $this->getDisk();

        if (! $disk->exists($from)) {
            return false;
        }

        return $disk->copy($from, $to);
    }

    public function generateVersionStoragePath(Document $document, int $versionNumber): string
    {
        $basePath = $this->generateStoragePath($document);
        $baseDir = dirname($basePath);
        $filename = basename($basePath);

        return "{$baseDir}/versions/{$versionNumber}/{$filename}";
    }

    public function exists(string $path): bool
    {
        return $this->getDisk()->exists($path);
    }

    public function getSize(string $path): int
    {
        return $this->getDisk()->size($path);
    }
}
