<?php

namespace Afterburner\Documents\Policies;

use Afterburner\Documents\Models\Folder;
use App\Models\User;

class FolderPolicy
{
    /**
     * Determine if the user can view any folders.
     */
    public function viewAny(User $user): bool
    {
        return true; // Team membership check happens at controller level
    }

    /**
     * Determine if the user can view the folder.
     */
    public function view(User $user, Folder $folder): bool
    {
        return $folder->canView($user);
    }

    /**
     * Determine if the user can create folders.
     */
    public function create(User $user): bool
    {
        return true; // Team membership check happens at controller level
    }

    /**
     * Determine if the user can update the folder.
     */
    public function update(User $user, Folder $folder): bool
    {
        return $folder->canEdit($user);
    }

    /**
     * Determine if the user can delete the folder.
     */
    public function delete(User $user, Folder $folder): bool
    {
        return $folder->canDelete($user);
    }
}

