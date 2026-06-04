<?php

namespace Afterburner\Documents\Policies;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Support\DocumentsPermissions;
use Afterburner\Documents\Support\SubscriptionEntitlementGate;
use Afterburner\Documents\Support\TeamDocumentSettings;
use Afterburner\Documents\Support\TeamPermissionGate;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any documents.
     */
    public function viewAny(User $user): bool
    {
        if (! $user->currentTeam) {
            return false;
        }

        return $this->access($user, $user->currentTeam);
    }

    /**
     * Determine whether the user can access documents for the given team.
     */
    public function access(User $user, Team $team): bool
    {
        if (! $user->belongsToTeam($team)) {
            return false;
        }

        if (! SubscriptionEntitlementGate::allows($team)) {
            return false;
        }

        return DocumentsPermissions::canAccessModule($user, $team);
    }

    /**
     * Determine whether the user can view the document.
     */
    public function view(User $user, Document $document): bool
    {
        // User must belong to the team
        if (! $user->belongsToTeam($document->team)) {
            return false;
        }

        return $this->allowsDocumentAction($user, $document->team, 'view_documents');
    }

    /**
     * Determine whether the user can create documents.
     */
    public function create(User $user, $team): bool
    {
        // User must belong to the team
        if (! $user->belongsToTeam($team)) {
            return false;
        }

        return $this->allowsDocumentAction($user, $team, 'create_documents');
    }

    /**
     * Determine whether the user can update the document.
     */
    public function update(User $user, Document $document): bool
    {
        // User must belong to the team
        if (! $user->belongsToTeam($document->team)) {
            return false;
        }

        return $this->allowsDocumentAction($user, $document->team, 'edit_documents');
    }

    /**
     * Determine whether the user can delete the document.
     */
    public function delete(User $user, Document $document): bool
    {
        // User must belong to the team
        if (! $user->belongsToTeam($document->team)) {
            return false;
        }

        if (! $this->allowsDocumentAction($user, $document->team, 'delete_documents')) {
            return false;
        }

        // Check if document is protected by retention
        if ($document->isRetentionProtected()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can download the document.
     */
    public function download(User $user, Document $document): bool
    {
        // User must belong to the team
        if (! $user->belongsToTeam($document->team)) {
            return false;
        }

        return $this->allowsDocumentAction($user, $document->team, 'download_documents');
    }

    /**
     * Determine whether the user can restore a document version.
     */
    public function restoreVersion(User $user, Document $document): bool
    {
        // User must belong to the team
        if (! $user->belongsToTeam($document->team)) {
            return false;
        }

        if (! TeamDocumentSettings::versioningEnabledForTeam($document->team)) {
            return false;
        }

        return $this->allowsDocumentAction($user, $document->team, 'restore_document_versions');
    }

    protected function allowsDocumentAction(User $user, Team $team, string $permission): bool
    {
        if (! SubscriptionEntitlementGate::allows($team)) {
            return false;
        }

        return TeamPermissionGate::allows($user, $team->id, $permission);
    }
}
