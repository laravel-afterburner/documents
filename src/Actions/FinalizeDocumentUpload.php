<?php

namespace Afterburner\Documents\Actions;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Notifications\DocumentUploadComplete;
use Afterburner\Documents\Services\StorageService;
use Afterburner\Documents\Support\DocumentUploadNotificationRules;
use Afterburner\Documents\Support\DocumentsAuditLogger;
use App\Models\User;

class FinalizeDocumentUpload
{
    public function __construct(
        protected StorageService $storageService
    ) {}

    /**
     * Move uploaded content to permanent storage and mark the document complete.
     *
     * @param  string  $sourcePath  Absolute path to the source file on disk
     */
    public function executeFromPath(Document $document, string $sourcePath, User $user): Document
    {
        $storagePath = $this->storageService->generateStoragePath($document);

        $document->updateQuietly([
            'storage_path' => $storagePath,
            'upload_status' => 'processing',
        ]);

        $success = $this->storageService->storeDocumentFromPath($sourcePath, $storagePath);

        if (! $success) {
            $document->updateQuietly(['upload_status' => 'failed']);

            throw new \RuntimeException(
                'Failed to store document in storage. '.$this->storageService->storageFailureMessage()
            );
        }

        $document->updateQuietly([
            'upload_status' => 'completed',
            'upload_progress' => 100,
        ]);

        $document->createVersion($storagePath, (int) $document->size, $user);

        $this->logUploadAudit($document, $user);

        $this->notifyUploadCompleteIfNeeded($document, $user);

        return $document->fresh();
    }

    /**
     * Finalize from an upload session part file stored on the documents disk.
     */
    public function executeFromSessionPart(Document $document, string $sessionPartPath, User $user): Document
    {
        $storagePath = $this->storageService->generateStoragePath($document);

        $document->updateQuietly([
            'storage_path' => $storagePath,
            'upload_status' => 'processing',
        ]);

        $success = $this->storageService->finalizeUploadSessionPart($sessionPartPath, $storagePath);

        if (! $success) {
            $document->updateQuietly(['upload_status' => 'failed']);

            throw new \RuntimeException(
                'Failed to store document in storage. '.$this->storageService->storageFailureMessage()
            );
        }

        $document->updateQuietly([
            'upload_status' => 'completed',
            'upload_progress' => 100,
        ]);

        $document->createVersion($storagePath, (int) $document->size, $user);

        $this->logUploadAudit($document, $user);

        $this->notifyUploadCompleteIfNeeded($document, $user);

        return $document->fresh();
    }

    protected function notifyUploadCompleteIfNeeded(Document $document, User $user): void
    {
        if (! DocumentUploadNotificationRules::shouldNotifyOnComplete($document)) {
            return;
        }

        $user->notify(new DocumentUploadComplete($document));
    }

    protected function logUploadAudit(Document $document, User $user): void
    {
        DocumentsAuditLogger::documentUploaded($document, $user);
    }
}
