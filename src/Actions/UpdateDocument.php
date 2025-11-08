<?php

namespace Afterburner\Documents\Actions;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\DocumentVersion;
use Afterburner\Documents\Services\StorageService;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateDocument
{
    public function __construct(
        protected StorageService $storageService
    ) {
    }

    /**
     * Update a document and create a new version.
     *
     * @param  Document  $document
     * @param  array  $attributes
     * @param  string|null  $newFileContent  New file content if updating file
     * @param  User  $user
     * @return Document
     */
    public function execute(Document $document, array $attributes, ?string $newFileContent, User $user): Document
    {
        return DB::transaction(function () use ($document, $attributes, $newFileContent, $user) {
            $oldAttributes = $document->getAttributes();

            // Create version of current document if versioning is enabled
            if (config('afterburner-documents.versioning.enabled', true) &&
                config('afterburner-documents.versioning.auto_version_on_update', true) &&
                $newFileContent) {
                // Copy current version to version history
                $currentVersion = $document->currentVersion();
                if ($currentVersion) {
                    // Version already exists, no need to create again
                } else {
                    // Create version from current document
                    $document->createVersion(
                        $document->storage_path,
                        $document->size,
                        $user
                    );
                }
            }

            // Update document
            $document->update($attributes);

            // If new file content provided, upload it
            if ($newFileContent) {
                $newStoragePath = $this->storageService->generateStoragePath($document);
                $this->storageService->storeDocument($newFileContent, $newStoragePath);

                // Create new version
                $newVersion = $document->createVersion($newStoragePath, strlen($newFileContent), $user);

                $document->update([
                    'storage_path' => $newStoragePath,
                    'size' => strlen($newFileContent),
                ]);

                // Create audit log for version creation
                AuditLog::create([
                    'user_id' => $user->id,
                    'action_type' => 'created',
                    'category' => 'documents',
                    'event_name' => 'document.version.created',
                    'auditable_type' => DocumentVersion::class,
                    'auditable_id' => $newVersion->id,
                    'team_id' => $document->team_id,
                    'changes' => [
                        'version_number' => $newVersion->version_number,
                        'document_id' => $document->id,
                    ],
                ]);
            }

            // Create audit log for document update
            $changes = [];
            foreach ($attributes as $key => $value) {
                if (isset($oldAttributes[$key]) && $oldAttributes[$key] != $value) {
                    $changes[$key] = [
                        'before' => $oldAttributes[$key],
                        'after' => $value,
                    ];
                }
            }

            if (!empty($changes)) {
                AuditLog::create([
                    'user_id' => $user->id,
                    'action_type' => 'updated',
                    'category' => 'documents',
                    'event_name' => 'document.updated',
                    'auditable_type' => Document::class,
                    'auditable_id' => $document->id,
                    'team_id' => $document->team_id,
                    'changes' => $changes,
                ]);
            }

            return $document->fresh();
        });
    }
}

