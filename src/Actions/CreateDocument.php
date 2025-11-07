<?php

namespace Afterburner\Documents\Actions;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\DocumentAudit;
use Afterburner\Documents\Models\DocumentPermission;
use Afterburner\Documents\Models\DocumentVersion;
use Afterburner\Documents\Services\DocumentStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateDocument
{
    public function __construct(
        protected DocumentStorageService $storageService
    ) {
    }

    /**
     * Create a new document.
     *
     * @param  array  $data  Document data
     * @param  \Illuminate\Http\UploadedFile|string|null  $file  The file to upload (optional if using chunked upload)
     * @param  mixed  $user  The user creating the document
     * @return \Afterburner\Documents\Models\Document
     */
    public function execute(array $data, $file = null, $user = null): Document
    {
        return DB::transaction(function () use ($data, $file, $user) {
            // Determine filename first
            $filename = $data['filename'] ?? ($file instanceof UploadedFile ? $file->getClientOriginalName() : basename($file));
            $originalFilename = $data['original_filename'] ?? ($file instanceof UploadedFile ? $file->getClientOriginalName() : basename($file));
            
            // Generate temporary storage path using UUID (will be updated with actual document ID after creation)
            $tempDocument = new Document([
                'team_id' => $data['team_id'],
                'filename' => $filename,
            ]);
            $tempStoragePath = $this->storageService->generateStoragePath($tempDocument);
            
            // If storage_path is provided (from chunked upload), use it
            if (isset($data['storage_path'])) {
                $tempStoragePath = $data['storage_path'];
            }
            
            // Create document record with temporary storage_path (required field)
            $document = Document::create([
                'team_id' => $data['team_id'],
                'folder_id' => $data['folder_id'] ?? null,
                'name' => $data['name'],
                'filename' => $filename,
                'original_filename' => $originalFilename,
                'mime_type' => $data['mime_type'] ?? ($file instanceof UploadedFile ? $file->getMimeType() : ($file ? mime_content_type($file) : 'application/octet-stream')),
                'file_size' => $data['file_size'] ?? ($file instanceof UploadedFile ? $file->getSize() : ($file ? filesize($file) : 0)),
                'storage_path' => $tempStoragePath,
                'storage_disk' => $data['storage_disk'] ?? 'r2',
                'version' => 1,
                'created_by' => $user?->id ?? auth()->id(),
                'retention_tag_id' => $data['retention_tag_id'] ?? null,
                'retention_expires_at' => $data['retention_expires_at'] ?? null,
            ]);

            // Store file if provided
            if ($file && !isset($data['storage_path'])) {
                // Regular upload - store file and generate path with actual document ID
                $storagePath = $this->storageService->store($file, $document);
                $document->update(['storage_path' => $storagePath]);
            } elseif (isset($data['storage_path'])) {
                // For chunked uploads, storage_path is already provided and file is already stored
                // Keep the provided path as-is (don't regenerate with document ID)
                // The path was generated before document creation and file is already at that location
            } else {
                // No file provided, generate final path with actual document ID
                $finalStoragePath = $this->storageService->generateStoragePath($document);
                $document->update(['storage_path' => $finalStoragePath]);
            }

            // Create initial version
            if (config('afterburner-documents.versioning.enabled', true)) {
                DocumentVersion::create([
                    'document_id' => $document->id,
                    'version_number' => 1,
                    'filename' => $document->filename,
                    'storage_path' => $document->storage_path,
                    'file_size' => $document->file_size,
                    'mime_type' => $document->mime_type,
                    'created_by' => $document->created_by,
                    'change_summary' => 'Initial version',
                ]);
            }

            // Set default permissions
            $this->setDefaultPermissions($document);

            // Set retention expiration if retention tag is set
            if ($document->retention_tag_id && $document->retentionTag) {
                $document->update([
                    'retention_expires_at' => $document->retentionTag->getExpirationDate(),
                ]);
            }

            // Log audit trail
            DocumentAudit::logAction(
                $document,
                $user ?? auth()->user(),
                'created',
                [
                    'filename' => $document->filename,
                    'folder_id' => $document->folder_id,
                ]
            );

            return $document->fresh();
        });
    }

    /**
     * Set default permissions for a document based on config.
     *
     * @param  \Afterburner\Documents\Models\Document  $document
     * @return void
     */
    protected function setDefaultPermissions(Document $document): void
    {
        $defaultViewRoles = config('afterburner-documents.permissions.default_view_roles', ['member']);
        $defaultEditRoles = config('afterburner-documents.permissions.default_edit_roles', ['admin', 'owner']);
        $defaultDeleteRoles = config('afterburner-documents.permissions.default_delete_roles', ['admin', 'owner']);

        // Get all unique roles
        $allRoles = array_unique(array_merge($defaultViewRoles, $defaultEditRoles, $defaultDeleteRoles));

        foreach ($allRoles as $roleSlug) {
            DocumentPermission::create([
                'document_id' => $document->id,
                'role_slug' => $roleSlug,
                'can_view' => in_array($roleSlug, $defaultViewRoles),
                'can_edit' => in_array($roleSlug, $defaultEditRoles),
                'can_delete' => in_array($roleSlug, $defaultDeleteRoles),
                'can_share' => in_array($roleSlug, $defaultEditRoles), // Can share if can edit
            ]);
        }
    }
}

