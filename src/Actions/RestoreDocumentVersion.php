<?php

namespace Afterburner\Documents\Actions;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\DocumentVersion;
use Afterburner\Documents\Services\StorageService;
use App\Models\AuditLog;
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

            // Create audit log entry
            AuditLog::create([
                'user_id' => $user->id,
                'action_type' => 'restored',
                'category' => 'documents',
                'event_name' => 'document.version.restored',
                'auditable_type' => DocumentVersion::class,
                'auditable_id' => $version->id,
                'team_id' => $document->team_id,
                'changes' => [
                    'version_number' => $version->version_number,
                    'document_id' => $document->id,
                    'document_name' => $document->name,
                ],
            ]);

            return $document->fresh();
        });
    }
}

