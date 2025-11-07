<?php

namespace Afterburner\Documents\Policies;

use Afterburner\Documents\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Determine if the user can view any documents.
     */
    public function viewAny(User $user): bool
    {
        return true; // Team membership check happens at controller level
    }

    /**
     * Determine if the user can view the document.
     */
    public function view(User $user, Document $document): bool
    {
        return $document->canView($user);
    }

    /**
     * Determine if the user can create documents.
     */
    public function create(User $user): bool
    {
        return true; // Team membership check happens at controller level
    }

    /**
     * Determine if the user can update the document.
     */
    public function update(User $user, Document $document): bool
    {
        return $document->canEdit($user);
    }

    /**
     * Determine if the user can delete the document.
     */
    public function delete(User $user, Document $document): bool
    {
        return $document->canDelete($user);
    }

    /**
     * Determine if the user can download the document.
     */
    public function download(User $user, Document $document): bool
    {
        return $document->canView($user);
    }

    /**
     * Determine if the user can share the document.
     */
    public function share(User $user, Document $document): bool
    {
        if (!$document->canView($user)) {
            return false;
        }

        // Check if user has share permission
        $userRoles = $user->roles()->where('team_id', $document->team_id)->pluck('slug')->toArray();

        $permission = $document->permissions()
            ->whereIn('role_slug', $userRoles)
            ->where('can_share', true)
            ->first();

        return $permission !== null;
    }
}

