<?php

namespace Afterburner\Documents\Actions;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\DocumentVersion;
use Afterburner\Documents\Services\StorageService;
use Afterburner\Documents\Support\TeamDocumentSettings;
use Afterburner\Documents\Support\DocumentsAuditLogger;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateDocument
{
    public function __construct(
        protected StorageService $storageService
    ) {}

    /**
     * Update a document and optionally replace its file.
     *
     * @param  string|null  $newFileContent  New file content if updating file
     */
    public function execute(Document $document, array $attributes, ?string $newFileContent, User $user): Document
    {
        return DB::transaction(function () use ($document, $attributes, $newFileContent, $user) {
            $oldAttributes = $document->getAttributes();

            if ($newFileContent) {
                $this->replaceDocumentFile($document, $attributes, $newFileContent, $user);
            } elseif (! empty($attributes)) {
                $document->update($attributes);
            }

            $this->logDocumentUpdate($document, $oldAttributes, $attributes, $user);

            return $document->fresh();
        });
    }

    protected function replaceDocumentFile(
        Document $document,
        array $attributes,
        string $newFileContent,
        User $user
    ): void {
        $oldStoragePath = $document->storage_path;
        $oldSize = (int) $document->size;

        if (TeamDocumentSettings::versioningEnabledForTeam($document->team_id)
            && config('afterburner-documents.versioning.auto_version_on_update', true)
            && $oldStoragePath !== ''
            && $this->storageService->exists($oldStoragePath)) {
            $this->archiveCurrentFileAsVersion($document, $oldStoragePath, $oldSize, $user);
        }

        $document->update($attributes);

        $storagePath = $oldStoragePath !== ''
            ? $oldStoragePath
            : $this->storageService->generateStoragePath($document);

        if (! $this->storageService->storeDocument($newFileContent, $storagePath)) {
            throw new \RuntimeException(
                'Failed to store document in storage. '.$this->storageService->storageFailureMessage()
            );
        }

        $document->update([
            'storage_path' => $storagePath,
            'size' => strlen($newFileContent),
        ]);
    }

    protected function archiveCurrentFileAsVersion(
        Document $document,
        string $storagePath,
        int $size,
        User $user
    ): void {
        $nextVersionNumber = ($document->versions()->max('version_number') ?? 0) + 1;
        $versionPath = $this->storageService->generateVersionStoragePath($document, $nextVersionNumber);

        if (! $this->storageService->copy($storagePath, $versionPath)) {
            throw new \RuntimeException('Failed to archive the current document version before replacing the file.');
        }

        $version = $document->createVersion($versionPath, $size, $user);

        DocumentsAuditLogger::documentVersionCreated($version, $document, $user);
    }

    protected function logDocumentUpdate(
        Document $document,
        array $oldAttributes,
        array $attributes,
        User $user
    ): void {
        $changes = [];

        foreach ($attributes as $key => $value) {
            if (isset($oldAttributes[$key]) && $oldAttributes[$key] != $value) {
                $changes[$key] = [
                    'before' => $oldAttributes[$key],
                    'after' => $value,
                ];
            }
        }

        if (empty($changes)) {
            return;
        }

        DocumentsAuditLogger::documentUpdated($document, $user, $changes);
    }
}
