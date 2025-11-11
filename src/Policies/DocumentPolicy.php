<?php

namespace Afterburner\Documents\Policies;

use Afterburner\Documents\Models\Document;
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
        return true;
    }

    /**
     * Determine whether the user can view the document.
     */
    public function view(User $user, Document $document): bool
    {
        // User must belong to the team
        if (!$user->belongsToTeam($document->team)) {
            return false;
        }

        // Check for view_documents permission
        return $user->hasPermission('view_documents', $document->team->id);
    }

    /**
     * Determine whether the user can create documents.
     */
    public function create(User $user, $team): bool
    {
        // User must belong to the team
        if (!$user->belongsToTeam($team)) {
            return false;
        }

        // Check for create_documents permission
        return $user->hasPermission('create_documents', $team->id);
    }

    /**
     * Determine whether the user can update the document.
     */
    public function update(User $user, Document $document): bool
    {
        // User must belong to the team
        if (!$user->belongsToTeam($document->team)) {
            return false;
        }

        // Check for edit_documents permission
        return $user->hasPermission('edit_documents', $document->team->id);
    }

    /**
     * Determine whether the user can delete the document.
     */
    public function delete(User $user, Document $document): bool
    {
        // User must belong to the team
        if (!$user->belongsToTeam($document->team)) {
            return false;
        }

        // Check for delete_documents permission
        if (!$user->hasPermission('delete_documents', $document->team->id)) {
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
        if (!$user->belongsToTeam($document->team)) {
            return false;
        }

        // Check for download_documents permission
        return $user->hasPermission('download_documents', $document->team->id);
    }

    /**
     * Determine whether the user can restore a document version.
     */
    public function restoreVersion(User $user, Document $document): bool
    {
        // User must belong to the team
        if (!$user->belongsToTeam($document->team)) {
            return false;
        }

        // Check for restore_document_versions permission
        return $user->hasPermission('restore_document_versions', $document->team->id);
    }
}

