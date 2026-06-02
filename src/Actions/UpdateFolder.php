<?php

namespace Afterburner\Documents\Actions;

use Afterburner\Documents\Models\Folder;
use Afterburner\Documents\Support\DocumentsAuditLogger;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateFolder
{
    /**
     * Update a folder.
     *
     * @param  Folder  $folder
     * @param  array  $attributes
     * @param  User  $user
     * @return Folder
     */
    public function execute(Folder $folder, array $attributes, User $user): Folder
    {
        return DB::transaction(function () use ($folder, $attributes, $user) {
            $oldAttributes = $folder->getAttributes();

            // Check for duplicate folder name in same location (if name is being changed)
            if (isset($attributes['name']) && $attributes['name'] !== $folder->name) {
                $existing = Folder::where('team_id', $folder->team_id)
                    ->where('parent_id', $folder->parent_id)
                    ->where('name', $attributes['name'])
                    ->where('id', '!=', $folder->id)
                    ->first();

                if ($existing) {
                    throw new \Exception("A folder with the name '{$attributes['name']}' already exists in this location.");
                }
            }

            // Update folder
            $folder->update($attributes);

            // Create audit log entry
            $changes = [];
            foreach ($attributes as $key => $value) {
                if (isset($oldAttributes[$key]) && $oldAttributes[$key] != $value) {
                    $changes[$key] = [
                        'before' => $oldAttributes[$key],
                        'after' => $value,
                    ];
                }
            }

            DocumentsAuditLogger::folderUpdated($folder, $user, $changes);

            return $folder->fresh();
        });
    }
}

