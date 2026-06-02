<?php

namespace Afterburner\Documents\Support;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\DocumentVersion;
use Afterburner\Documents\Models\Folder;
use Afterburner\Documents\Models\RetentionTag;
use App\Models\User;
use App\Support\Audit\AuditLogger;

class DocumentsAuditLogger
{
    public const CATEGORY = 'documents';

    public static function folderCreated(Folder $folder, User $user): void
    {
        self::log(
            'folder.created',
            $folder,
            "{$user->name} created folder \"{$folder->name}\".",
            ['name' => $folder->name, 'parent_id' => $folder->parent_id],
            $folder->team_id,
            $user,
        );
    }

    public static function folderUpdated(Folder $folder, User $user, array $fieldChanges): void
    {
        if ($fieldChanges === []) {
            return;
        }

        self::log(
            'folder.updated',
            $folder,
            "{$user->name} updated folder \"{$folder->name}\".",
            array_merge(['summary' => "{$user->name} updated folder \"{$folder->name}\"."], $fieldChanges),
            $folder->team_id,
            $user,
        );
    }

    public static function documentUploaded(Document $document, User $user): void
    {
        self::log(
            'document.uploaded',
            $document,
            "{$user->name} uploaded document \"{$document->name}\".",
            [
                'name' => $document->name,
                'filename' => $document->filename,
                'folder_id' => $document->folder_id,
            ],
            $document->team_id,
            $user,
        );
    }

    /**
     * @param  array<string, array{before: mixed, after: mixed}>  $fieldChanges
     */
    public static function documentUpdated(Document $document, User $user, array $fieldChanges): void
    {
        if ($fieldChanges === []) {
            return;
        }

        self::log(
            'document.updated',
            $document,
            "{$user->name} updated document \"{$document->name}\".",
            array_merge(['summary' => "{$user->name} updated document \"{$document->name}\"."], $fieldChanges),
            $document->team_id,
            $user,
        );
    }

    public static function documentDeleted(Document $document, User $user, bool $permanent = false): void
    {
        self::log(
            'document.deleted',
            $document,
            "{$user->name} deleted document \"{$document->name}\".",
            [
                'name' => $document->name,
                'filename' => $document->filename,
                'permanent' => $permanent,
            ],
            $document->team_id,
            $user,
        );
    }

    public static function documentVersionCreated(DocumentVersion $version, Document $document, User $user): void
    {
        self::log(
            'document.version.created',
            $version,
            "{$user->name} archived version {$version->version_number} of \"{$document->name}\".",
            [
                'version_number' => $version->version_number,
                'document_id' => $document->id,
                'document_name' => $document->name,
            ],
            $document->team_id,
            $user,
        );
    }

    public static function documentVersionRestored(Document $document, User $user, int $versionNumber): void
    {
        self::log(
            'document.version.restored',
            $document,
            "{$user->name} restored version {$versionNumber} of \"{$document->name}\".",
            ['version_number' => $versionNumber, 'name' => $document->name],
            $document->team_id,
            $user,
        );
    }

    public static function retentionTagCreated(RetentionTag $tag, User $user): void
    {
        self::log(
            'retention_tag.created',
            $tag,
            "{$user->name} created retention tag \"{$tag->name}\".",
            ['name' => $tag->name, 'retention_period_days' => $tag->retention_period_days],
            $tag->team_id,
            $user,
        );
    }

    /**
     * @param  array<string, array{before: mixed, after: mixed}>  $fieldChanges
     */
    public static function retentionTagUpdated(RetentionTag $tag, User $user, array $fieldChanges): void
    {
        if ($fieldChanges === []) {
            return;
        }

        self::log(
            'retention_tag.updated',
            $tag,
            "{$user->name} updated retention tag \"{$tag->name}\".",
            array_merge(['summary' => "{$user->name} updated retention tag \"{$tag->name}\"."], $fieldChanges),
            $tag->team_id,
            $user,
        );
    }

    public static function retentionTagAssigned(Document $document, RetentionTag $tag, User $user): void
    {
        self::log(
            'document.retention_tag.assigned',
            $document,
            "{$user->name} assigned retention tag \"{$tag->name}\" to \"{$document->name}\".",
            ['document_name' => $document->name, 'retention_tag' => $tag->name],
            $document->team_id,
            $user,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected static function log(
        string $eventName,
        object $auditable,
        string $summary,
        array $context,
        int $teamId,
        User $user,
    ): void {
        AuditLogger::log(
            category: self::CATEGORY,
            eventName: $eventName,
            auditable: $auditable,
            changes: AuditLogger::changesWithSummary($summary, context: $context),
            metadata: ['actor_user_id' => $user->id],
            teamId: $teamId,
            actionType: 'action_class',
        );
    }
}
