<?php

namespace Afterburner\Documents\Actions;

use Afterburner\Documents\Exceptions\DuplicateDocumentException;
use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Services\StorageService;
use Afterburner\Documents\Support\SubscriptionEntitlementGate;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class UploadDocument
{
    public function __construct(
        protected StorageService $storageService
    ) {}

    /**
     * Initialize a document record for upload.
     */
    public function execute(
        int $teamId,
        ?int $folderId,
        string $filename,
        string $mimeType,
        int $size,
        User $user,
        bool $overwrite = false,
        ?string $notes = null
    ): Document {
        $team = Team::query()->findOrFail($teamId);

        if (! SubscriptionEntitlementGate::allows($team)) {
            throw new AuthorizationException('Documents are not included in your subscription plan.');
        }

        if (! SubscriptionEntitlementGate::allowsStorageForUpload($team, $size)) {
            throw new AuthorizationException('Storage limit exceeded for your subscription plan.');
        }

        $name = pathinfo($filename, PATHINFO_FILENAME);
        $existing = $this->findExistingDocument($teamId, $folderId, $name);

        if ($existing?->trashed()) {
            $this->purgeSoftDeletedDocument($existing);
            $existing = null;
        }

        if ($existing && ! $overwrite) {
            throw DuplicateDocumentException::inFolder($name);
        }

        if ($existing && $overwrite) {
            $existing->updateQuietly([
                'filename' => $filename,
                'mime_type' => $mimeType,
                'size' => $size,
                'upload_status' => 'pending',
                'upload_progress' => 0,
                'uploaded_by' => $user->id,
                'notes' => $notes,
            ]);

            return $existing;
        }

        $document = new Document([
            'team_id' => $teamId,
            'folder_id' => $folderId,
            'name' => $name,
            'notes' => $notes,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'size' => $size,
            'storage_path' => '',
            'upload_status' => 'pending',
            'upload_progress' => 0,
            'uploaded_by' => $user->id,
        ]);
        $document->saveQuietly();

        return $document;
    }

    protected function findExistingDocument(int $teamId, ?int $folderId, string $name): ?Document
    {
        return Document::withTrashed()
            ->where('team_id', $teamId)
            ->where('folder_id', $folderId)
            ->where('name', $name)
            ->first();
    }

    protected function purgeSoftDeletedDocument(Document $document): void
    {
        $document->loadMissing('versions');

        $this->storageService->deleteDocumentStorage($document);
        $document->versions()->delete();
        $document->forceDelete();
    }
}
