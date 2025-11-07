<?php

namespace Afterburner\Documents\Actions;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\DocumentAudit;
use Afterburner\Documents\Services\DocumentStorageService;
use Illuminate\Support\Facades\DB;

class DeleteDocument
{
    public function __construct(
        protected DocumentStorageService $storageService
    ) {
    }

    /**
     * Delete a document (soft delete by default).
     *
     * @param  \Afterburner\Documents\Models\Document  $document  The document to delete
     * @param  mixed  $user  The user deleting the document
     * @param  bool  $force  Whether to force delete (permanently delete)
     * @param  bool  $deleteFiles  Whether to delete files from storage
     * @return bool
     */
    public function execute(Document $document, $user = null, bool $force = false, bool $deleteFiles = false): bool
    {
        return DB::transaction(function () use ($document, $user, $force, $deleteFiles) {
            // Log audit trail before deletion
            DocumentAudit::logAction(
                $document,
                $user ?? auth()->user(),
                'deleted',
                [
                    'force' => $force,
                    'delete_files' => $deleteFiles,
                ]
            );

            // Delete files from storage if requested
            if ($deleteFiles) {
                $this->storageService->delete($document, true);
            }

            if ($force) {
                // Permanently delete
                $document->permissions()->delete();
                $document->versions()->delete();
                $document->audits()->delete();
                return $document->forceDelete();
            } else {
                // Soft delete
                return $document->delete();
            }
        });
    }
}

