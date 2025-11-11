<?php

namespace Afterburner\Documents\Actions;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\RetentionTag;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AssignRetentionTag
{
    /**
     * Assign a retention tag to a document.
     *
     * @param  Document  $document
     * @param  RetentionTag|null  $retentionTag
     * @param  User  $user
     * @return Document
     */
    public function execute(Document $document, ?RetentionTag $retentionTag, User $user): Document
    {
        return DB::transaction(function () use ($document, $retentionTag, $user) {
            $oldTagId = $document->retention_tag_id;

            if ($retentionTag) {
                // Verify tag belongs to same team
                if ($retentionTag->team_id !== $document->team_id) {
                    throw new \Exception('Retention tag does not belong to this team.');
                }

                // Calculate expiration date
                $expiresAt = now()->addDays($retentionTag->retention_period_days);

                $document->update([
                    'retention_tag_id' => $retentionTag->id,
                    'retention_expires_at' => $expiresAt,
                ]);
            } else {
                // Remove retention tag
                $document->update([
                    'retention_tag_id' => null,
                    'retention_expires_at' => null,
                ]);
            }

            // Create audit log entry
            if ($oldTagId !== $document->retention_tag_id) {
                AuditLog::create([
                    'user_id' => $user->id,
                    'action_type' => 'updated',
                    'category' => 'documents',
                    'event_name' => 'document.retention_tag.assigned',
                    'auditable_type' => Document::class,
                    'auditable_id' => $document->id,
                    'team_id' => $document->team_id,
                    'changes' => [
                        'retention_tag_id' => [
                            'before' => $oldTagId,
                            'after' => $retentionTag?->id,
                        ],
                        'retention_expires_at' => [
                            'before' => $document->getOriginal('retention_expires_at'),
                            'after' => $document->retention_expires_at?->toDateTimeString(),
                        ],
                    ],
                ]);
            }

            return $document->fresh();
        });
    }
}

