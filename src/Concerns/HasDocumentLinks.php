<?php

namespace Afterburner\Documents\Concerns;

use Afterburner\Documents\Models\Document;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasDocumentLinks
{
    /**
     * Documents linked to this model (ballots, meetings, etc.).
     */
    public function linkedDocuments(): MorphToMany
    {
        return $this->morphToMany(
            Document::class,
            'linkable',
            'document_links',
            'linkable_id',
            'document_id'
        )
            ->withTimestamps()
            ->withPivot(['team_id', 'linked_by_user_id'])
            ->orderBy('document_links.created_at');
    }
}
