<?php

namespace Afterburner\Documents\Actions;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\DocumentAudit;
use Afterburner\Documents\Models\DocumentVersion;
use Afterburner\Documents\Services\DocumentStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UpdateDocument
{
    public function __construct(
        protected DocumentStorageService $storageService
    ) {
    }

    /**
     * Update a document.
     *
     * @param  \Afterburner\Documents\Models\Document  $document  The document to update
     * @param  array  $data  Update data
     * @param  \Illuminate\Http\UploadedFile|string|null  $file  Optional new file to upload
     * @param  mixed  $user  The user updating the document
     * @return \Afterburner\Documents\Models\Document
     */
    public function execute(Document $document, array $data, $file = null, $user = null): Document
    {
        return DB::transaction(function () use ($document, $data, $file, $user) {
            $fileChanged = false;
            $oldStoragePath = $document->storage_path;

            // Handle file update if provided
            if ($file) {
                $fileChanged = true;

                // Create new version if versioning is enabled
                if (config('afterburner-documents.versioning.enabled', true) &&
                    config('afterburner-documents.versioning.auto_version_on_update', true)) {
                    $this->createVersion($document, $oldStoragePath, $user);
                }

                // Store new file
                $newStoragePath = $this->storageService->store($file, $document);
                $data['storage_path'] = $newStoragePath;
                $data['filename'] = $data['filename'] ?? ($file instanceof UploadedFile ? $file->getClientOriginalName() : basename($file));
                $data['mime_type'] = $data['mime_type'] ?? ($file instanceof UploadedFile ? $file->getMimeType() : mime_content_type($file));
                $data['file_size'] = $data['file_size'] ?? ($file instanceof UploadedFile ? $file->getSize() : filesize($file));
                $data['version'] = $document->version + 1;
            } elseif (isset($data['storage_path'])) {
                // Use provided storage path (from chunked upload)
                $fileChanged = true;

                if (config('afterburner-documents.versioning.enabled', true) &&
                    config('afterburner-documents.versioning.auto_version_on_update', true)) {
                    $this->createVersion($document, $oldStoragePath, $user);
                }

                $data['version'] = $document->version + 1;
            }

            // Update document
            $data['updated_by'] = $user?->id ?? auth()->id();
            $document->update($data);

            // Create version record for new file if versioning is enabled
            if ($fileChanged && config('afterburner-documents.versioning.enabled', true)) {
                DocumentVersion::create([
                    'document_id' => $document->id,
                    'version_number' => $document->version,
                    'filename' => $document->filename,
                    'storage_path' => $document->storage_path,
                    'file_size' => $document->file_size,
                    'mime_type' => $document->mime_type,
                    'created_by' => $document->updated_by,
                    'change_summary' => $data['change_summary'] ?? 'File updated',
                ]);
            }

            // Update retention expiration if retention tag changed
            if (isset($data['retention_tag_id']) && $document->retentionTag) {
                $document->update([
                    'retention_expires_at' => $document->retentionTag->getExpirationDate(),
                ]);
            }

            // Log audit trail
            DocumentAudit::logAction(
                $document,
                $user ?? auth()->user(),
                'updated',
                [
                    'file_changed' => $fileChanged,
                    'changes' => array_keys($data),
                ]
            );

            return $document->fresh();
        });
    }

    /**
     * Create a version record for the current file before updating.
     *
     * @param  \Afterburner\Documents\Models\Document  $document
     * @param  string  $storagePath
     * @param  mixed  $user
     * @return void
     */
    protected function createVersion(Document $document, string $storagePath, $user): void
    {
        // Check if version already exists
        $existingVersion = DocumentVersion::where('document_id', $document->id)
            ->where('version_number', $document->version)
            ->first();

        if (!$existingVersion) {
            DocumentVersion::create([
                'document_id' => $document->id,
                'version_number' => $document->version,
                'filename' => $document->filename,
                'storage_path' => $storagePath,
                'file_size' => $document->file_size,
                'mime_type' => $document->mime_type,
                'created_by' => $user?->id ?? auth()->id(),
                'change_summary' => 'Previous version before update',
            ]);
        }
    }
}

