<?php

namespace Afterburner\Documents\Concerns;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\Folder;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasDocuments
{
    /**
     * Get all documents for this team.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'team_id');
    }

    /**
     * Get all folders for this team.
     */
    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class, 'team_id');
    }

    /**
     * Get root folders (folders with no parent) for this team.
     */
    public function rootFolders(): HasMany
    {
        return $this->folders()->whereNull('parent_id');
    }
}

