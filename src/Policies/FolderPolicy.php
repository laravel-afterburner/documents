<?php

namespace Afterburner\Documents\Policies;

use Afterburner\Documents\Models\Folder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FolderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any folders.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the folder.
     */
    public function view(User $user, Folder $folder): bool
    {
        // User must belong to the team
        if (!$user->belongsToTeam($folder->team)) {
            return false;
        }

        // Check for view_documents permission (folders are part of documents)
        return $user->hasPermission('view_documents', $folder->team->id);
    }

    /**
     * Determine whether the user can create folders.
     */
    public function create(User $user, $team): bool
    {
        // User must belong to the team
        if (!$user->belongsToTeam($team)) {
            return false;
        }

        // Check for manage_folders permission
        return $user->hasPermission('manage_folders', $team->id);
    }

    /**
     * Determine whether the user can update the folder.
     */
    public function update(User $user, Folder $folder): bool
    {
        // User must belong to the team
        if (!$user->belongsToTeam($folder->team)) {
            return false;
        }

        // Check for manage_folders permission
        return $user->hasPermission('manage_folders', $folder->team->id);
    }

    /**
     * Determine whether the user can delete the folder.
     */
    public function delete(User $user, Folder $folder): bool
    {
        // User must belong to the team
        if (!$user->belongsToTeam($folder->team)) {
            return false;
        }

        // Check for manage_folders permission
        return $user->hasPermission('manage_folders', $folder->team->id);
    }
}

