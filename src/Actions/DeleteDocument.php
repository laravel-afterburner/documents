<?php

namespace Afterburner\Documents\Actions;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Services\StorageService;
use App\Models\AuditLog;
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

            AuditLog::create([
                'user_id' => $user->id,
                'action_type' => 'deleted',
                'category' => 'documents',
                'event_name' => 'document.deleted',
                'auditable_type' => Document::class,
                'auditable_id' => $document->id,
                'team_id' => $document->team_id,
                'changes' => [
                    'name' => $document->name,
                    'filename' => $document->filename,
                    'permanent' => $permanent,
                ],
            ]);

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
