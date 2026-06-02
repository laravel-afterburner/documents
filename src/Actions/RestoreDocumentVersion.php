<?php

namespace Afterburner\Documents\Actions;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\DocumentVersion;
use Afterburner\Documents\Services\StorageService;
use Afterburner\Documents\Support\DocumentsAuditLogger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RestoreDocumentVersion
{
    public function __construct(
        protected StorageService $storageService
    ) {
    }

    /**
     * Restore a document version.
     *
     * @param  Document  $document
     * @param  DocumentVersion  $version
     * @param  User  $user
     * @return Document
     */
    public function execute(Document $document, DocumentVersion $version, User $user): Document
    {
        // Verify version belongs to document
        if ($version->document_id !== $document->id) {
            throw new \Exception('Version does not belong to this document.');
        }

        return DB::transaction(function () use ($document, $version, $user) {
            $disk = Storage::disk('r2');

            // Verify version file exists
            if (!$disk->exists($version->storage_path)) {
                throw new \Exception('Version file not found in storage.');
            }

            // Create a version from the current document before restoring
            if ($disk->exists($document->storage_path)) {
                $document->createVersion(
                    $document->storage_path,
                    $document->size,
                    $user
                );
            }

            // Copy version file to current document storage path
            $versionContent = $disk->get($version->storage_path);
            $success = $disk->put($document->storage_path, $versionContent);

            if (!$success) {
                throw new \Exception('Failed to restore version file.');
            }

            // Update document with restored version data
            $document->update([
                'size' => $version->size,
                'filename' => basename($version->storage_path),
            ]);

            DocumentsAuditLogger::documentVersionRestored($document, $user, $version->version_number);

            return $document->fresh();
        });
    }
}

