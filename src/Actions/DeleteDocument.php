<?php

namespace Afterburner\Documents\Actions;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Services\StorageService;
use Afterburner\Documents\Support\DocumentsAuditLogger;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteDocument
{
    public function __construct(
        protected StorageService $storageService
    ) {}

    /**
     * Delete a document and remove its files from storage.
     */
    public function execute(Document $document, User $user, bool $permanent = false): bool
    {
        return DB::transaction(function () use ($document, $user, $permanent) {
            if ($document->isRetentionProtected()) {
                $expiresAt = $document->retention_expires_at->format('Y-m-d H:i:s');

                throw new \Exception(
                    "Cannot delete document '{$document->name}'. It is protected by retention until {$expiresAt}."
                );
            }

            $document->loadMissing('versions');

            DocumentsAuditLogger::documentDeleted($document, $user, $permanent);

            $this->storageService->deleteDocumentStorage($document);

            if ($permanent) {
                $document->versions()->delete();
                $document->forceDelete();
            } else {
                $document->delete();
            }

            return true;
        });
    }
}
