<?php

namespace Afterburner\Documents\Actions;

use Afterburner\Documents\Models\Folder;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateFolder
{
    /**
     * Create a new folder.
     *
     * @param  int  $teamId
     * @param  int|null  $parentId
     * @param  string  $name
     * @param  User  $user
     * @return Folder
     */
    public function execute(int $teamId, ?int $parentId, string $name, User $user): Folder
    {
        return DB::transaction(function () use ($teamId, $parentId, $name, $user) {
            // Check for duplicate folder name in same location
            $existing = Folder::where('team_id', $teamId)
                ->where('parent_id', $parentId)
                ->where('name', $name)
                ->first();

            if ($existing) {
                throw new \Exception("A folder with the name '{$name}' already exists in this location.");
            }

            // Create folder
            $folder = Folder::create([
                'team_id' => $teamId,
                'parent_id' => $parentId,
                'name' => $name,
                'created_by' => $user->id,
            ]);

            // Create audit log entry
            AuditLog::create([
                'user_id' => $user->id,
                'action_type' => 'created',
                'category' => 'documents',
                'event_name' => 'folder.created',
                'auditable_type' => Folder::class,
                'auditable_id' => $folder->id,
                'team_id' => $teamId,
                'changes' => [
                    'name' => $name,
                    'parent_id' => $parentId,
                ],
            ]);

            return $folder;
        });
    }
}

