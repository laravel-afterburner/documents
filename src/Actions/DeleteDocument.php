<?php

namespace Afterburner\Documents\Actions;

use Afterburner\Documents\Models\Document;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteDocument
{
    /**
     * Delete a document.
     *
     * @param  Document  $document
     * @param  User  $user
     * @param  bool  $permanent  Whether to permanently delete (including from storage)
     * @return bool
     */
    public function execute(Document $document, User $user, bool $permanent = false): bool
    {
        return DB::transaction(function () use ($document, $user, $permanent) {
            // Create audit log before deletion
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

            if ($permanent) {
                // Delete from storage
                if ($document->storage_path) {
                    \Illuminate\Support\Facades\Storage::disk('r2')->delete($document->storage_path);
                }

                // Delete versions
                foreach ($document->versions as $version) {
                    if ($version->storage_path) {
                        \Illuminate\Support\Facades\Storage::disk('r2')->delete($version->storage_path);
                    }
                }

                // Force delete from database
                $document->forceDelete();
            } else {
                // Soft delete
                $document->delete();
            }

            return true;
        });
    }
}

