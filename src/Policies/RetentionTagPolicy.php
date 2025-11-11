<?php

namespace Afterburner\Documents\Policies;

use Afterburner\Documents\Models\RetentionTag;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RetentionTagPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any retention tags.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the retention tag.
     */
    public function view(User $user, RetentionTag $retentionTag): bool
    {
        // User must belong to the team
        if (!$user->belongsToTeam($retentionTag->team)) {
            return false;
        }

        // Check for view_documents permission (retention tags are part of documents)
        return $user->hasPermission('view_documents', $retentionTag->team->id);
    }

    /**
     * Determine whether the user can create retention tags.
     */
    public function create(User $user, $team): bool
    {
        // User must belong to the team
        if (!$user->belongsToTeam($team)) {
            return false;
        }

        // Check for manage_retention_tags permission
        return $user->hasPermission('manage_retention_tags', $team->id);
    }

    /**
     * Determine whether the user can update the retention tag.
     */
    public function update(User $user, RetentionTag $retentionTag): bool
    {
        // User must belong to the team
        if (!$user->belongsToTeam($retentionTag->team)) {
            return false;
        }

        // Check for manage_retention_tags permission
        return $user->hasPermission('manage_retention_tags', $retentionTag->team->id);
    }

    /**
     * Determine whether the user can delete the retention tag.
     */
    public function delete(User $user, RetentionTag $retentionTag): bool
    {
        // User must belong to the team
        if (!$user->belongsToTeam($retentionTag->team)) {
            return false;
        }

        // Check for manage_retention_tags permission
        return $user->hasPermission('manage_retention_tags', $retentionTag->team->id);
    }
}

