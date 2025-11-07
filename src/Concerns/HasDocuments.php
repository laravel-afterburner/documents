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
        return $this->hasMany(Document::class);
    }

    /**
     * Get all folders for this team.
     */
    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    /**
     * Get root folders (folders with no parent) for this team.
     */
    public function rootFolders(): HasMany
    {
        return $this->hasMany(Folder::class)->whereNull('parent_id');
    }

    /**
     * Get the total number of documents for this team.
     */
    public function documentCount(): int
    {
        return $this->documents()->count();
    }

    /**
     * Get the total storage used by documents for this team (in bytes).
     */
    public function storageUsed(): int
    {
        return $this->documents()->sum('file_size');
    }

    /**
     * Get human-readable storage used.
     */
    public function storageUsedHuman(): string
    {
        $bytes = $this->storageUsed();
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2).' '.$units[$i];
    }
}

