<?php

namespace Afterburner\Documents\Actions;

use Afterburner\Documents\Models\Document;
use App\Models\User;

class UploadDocument
{
    /**
     * Initialize a document record for upload.
     *
     * @param  int  $teamId
     * @param  int|null  $folderId
     * @param  string  $filename
     * @param  string  $mimeType
     * @param  int  $size
     * @param  User  $user
     * @param  bool  $overwrite  Whether to overwrite existing document
     * @return Document
     */
    public function execute(
        int $teamId,
        ?int $folderId,
        string $filename,
        string $mimeType,
        int $size,
        User $user,
        bool $overwrite = false
    ): Document {
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // Check for existing document
        $existing = Document::where('team_id', $teamId)
            ->where('folder_id', $folderId)
            ->where('name', $name)
            ->first();

        if ($existing && !$overwrite) {
            throw new \Exception("Document with name '{$name}' already exists in this folder.");
        }

        if ($existing && $overwrite) {
            // Update existing document
            $existing->update([
                'filename' => $filename,
                'mime_type' => $mimeType,
                'size' => $size,
                'upload_status' => 'pending',
                'upload_progress' => 0,
                'uploaded_by' => $user->id,
            ]);

            return $existing;
        }

        // Create new document
        return Document::create([
            'team_id' => $teamId,
            'folder_id' => $folderId,
            'name' => $name,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'size' => $size,
            'storage_path' => '',
            'upload_status' => 'pending',
            'upload_progress' => 0,
            'uploaded_by' => $user->id,
        ]);
    }
}

