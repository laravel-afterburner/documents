<?php

namespace Afterburner\Documents\Actions;

use Afterburner\Documents\Models\Folder;
use App\Models\AuditLog;
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

            if (!empty($changes)) {
                AuditLog::create([
                    'user_id' => $user->id,
                    'action_type' => 'updated',
                    'category' => 'documents',
                    'event_name' => 'folder.updated',
                    'auditable_type' => Folder::class,
                    'auditable_id' => $folder->id,
                    'team_id' => $folder->team_id,
                    'changes' => $changes,
                ]);
            }

            return $folder->fresh();
        });
    }
}

