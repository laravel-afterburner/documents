<?php

namespace Afterburner\Documents\Actions;

use Afterburner\Documents\Exceptions\DocumentLinkException;
use Afterburner\Documents\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class UnlinkDocument
{
    public function execute(Document $document, Model $linkable, User $user): void
    {
        Gate::forUser($user)->authorize('view', $document);

        if (! method_exists($linkable, 'linkedDocuments')) {
            throw new DocumentLinkException('This record does not support document links.');
        }

        $teamId = $this->resolveTeamId($linkable);

        if ($document->team_id !== $teamId) {
            throw new DocumentLinkException('The document must belong to the same team as this record.');
        }

        $linkable->linkedDocuments()->detach($document->id);
    }

    protected function resolveTeamId(Model $linkable): int
    {
        if (isset($linkable->team_id)) {
            return (int) $linkable->team_id;
        }

        throw new DocumentLinkException('This record cannot be linked to documents.');
    }
}
