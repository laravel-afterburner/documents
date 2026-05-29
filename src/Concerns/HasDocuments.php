<?php

namespace Afterburner\Documents\Concerns;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\Folder;
use Afterburner\Documents\Models\TeamDocumentSetting;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    /**
     * Get document settings for this team.
     */
    public function documentSettings(): HasOne
    {
        return $this->hasOne(TeamDocumentSetting::class, 'team_id');
    }
}
