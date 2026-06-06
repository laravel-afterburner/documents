<?php

namespace Afterburner\Documents\Policies;

use Afterburner\Documents\Models\RetentionTag;
use Afterburner\Documents\Support\SubscriptionEntitlementGate;
use Afterburner\Documents\Support\TeamDocumentSettings;
use App\Support\TeamPermissionGate;
use App\Models\Team;
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
        if (! $user->currentTeam) {
            return false;
        }

        if (! TeamDocumentSettings::retentionEnabledForTeam($user->currentTeam)) {
            return false;
        }

        return SubscriptionEntitlementGate::allows($user->currentTeam);
    }

    /**
     * Determine whether the user can view the retention tag.
     */
    public function view(User $user, RetentionTag $retentionTag): bool
    {
        // User must belong to the team
        if (! $user->belongsToTeam($retentionTag->team)) {
            return false;
        }

        if (! TeamDocumentSettings::retentionEnabledForTeam($retentionTag->team)) {
            return false;
        }

        return $this->allowsRetentionTagAction($user, $retentionTag->team, 'view_documents');
    }

    /**
     * Determine whether the user can create retention tags.
     */
    public function create(User $user, $team): bool
    {
        // User must belong to the team
        if (! $user->belongsToTeam($team)) {
            return false;
        }

        if (! TeamDocumentSettings::retentionEnabledForTeam($team)) {
            return false;
        }

        return $this->allowsRetentionTagAction($user, $team, 'manage_retention_tags');
    }

    /**
     * Determine whether the user can update the retention tag.
     */
    public function update(User $user, RetentionTag $retentionTag): bool
    {
        // User must belong to the team
        if (! $user->belongsToTeam($retentionTag->team)) {
            return false;
        }

        if (! TeamDocumentSettings::retentionEnabledForTeam($retentionTag->team)) {
            return false;
        }

        return $this->allowsRetentionTagAction($user, $retentionTag->team, 'manage_retention_tags');
    }

    /**
     * Determine whether the user can delete the retention tag.
     */
    public function delete(User $user, RetentionTag $retentionTag): bool
    {
        // User must belong to the team
        if (! $user->belongsToTeam($retentionTag->team)) {
            return false;
        }

        if (! TeamDocumentSettings::retentionEnabledForTeam($retentionTag->team)) {
            return false;
        }

        return $this->allowsRetentionTagAction($user, $retentionTag->team, 'manage_retention_tags');
    }

    protected function allowsRetentionTagAction(User $user, Team $team, string $permission): bool
    {
        if (! SubscriptionEntitlementGate::allows($team)) {
            return false;
        }

        return TeamPermissionGate::allows($user, $team->id, $permission);
    }
}
