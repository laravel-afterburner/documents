<?php

namespace Afterburner\Documents\Policies;

use Afterburner\Documents\Models\Folder;
use Afterburner\Documents\Support\SubscriptionEntitlementGate;
use App\Support\TeamPermissionGate;
use App\Models\Team;
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
        if (! $user->belongsToTeam($folder->team)) {
            return false;
        }

        return $this->allowsFolderAction($user, $folder->team, 'view_documents');
    }

    /**
     * Determine whether the user can create folders.
     */
    public function create(User $user, $team): bool
    {
        // User must belong to the team
        if (! $user->belongsToTeam($team)) {
            return false;
        }

        return $this->allowsFolderAction($user, $team, 'manage_folders');
    }

    /**
     * Determine whether the user can update the folder.
     */
    public function update(User $user, Folder $folder): bool
    {
        // User must belong to the team
        if (! $user->belongsToTeam($folder->team)) {
            return false;
        }

        return $this->allowsFolderAction($user, $folder->team, 'manage_folders');
    }

    /**
     * Determine whether the user can delete the folder.
     */
    public function delete(User $user, Folder $folder): bool
    {
        // User must belong to the team
        if (! $user->belongsToTeam($folder->team)) {
            return false;
        }

        return $this->allowsFolderAction($user, $folder->team, 'manage_folders');
    }

    protected function allowsFolderAction(User $user, Team $team, string $permission): bool
    {
        if (! SubscriptionEntitlementGate::allows($team)) {
            return false;
        }

        return TeamPermissionGate::allows($user, $team->id, $permission);
    }
}
