<?php

namespace Afterburner\Documents\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Folder extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'parent_id',
        'name',
        'slug',
        'created_by',
    ];

    /**
     * Get the team that owns the folder.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class);
    }

    /**
     * Get the parent folder.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    /**
     * Get the child folders.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    /**
     * Get the documents in this folder.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get the user who created the folder.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Generate a unique slug for the folder.
     */
    public function generateSlug(): string
    {
        $baseSlug = Str::slug($this->name);
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('team_id', $this->team_id)
            ->where('slug', $slug)
            ->where('id', '!=', $this->id ?? 0)
            ->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get the breadcrumb path for this folder.
     */
    public function getPath(): array
    {
        $path = [];
        $folder = $this;

        while ($folder) {
            array_unshift($path, $folder);
            $folder = $folder->parent;
        }

        return $path;
    }

    /**
     * Scope a query to only include folders for a specific team.
     */
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope a query to only include root folders (no parent).
     */
    public function scopeRootFolders($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get all descendant folder IDs recursively.
     */
    public function getDescendantIds(): array
    {
        $descendantIds = [];
        $children = static::where('parent_id', $this->id)->get();

        foreach ($children as $child) {
            $descendantIds[] = $child->id;
            $descendantIds = array_merge($descendantIds, $child->getDescendantIds());
        }

        return $descendantIds;
    }

    /**
     * Get the total count of documents in this folder and all nested folders.
     */
    public function getTotalDocumentsCount(): int
    {
        $folderIds = array_merge([$this->id], $this->getDescendantIds());
        
        return Document::whereIn('folder_id', $folderIds)->count();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($folder) {
            if (empty($folder->slug)) {
                $folder->slug = $folder->generateSlug();
            }
        });

        static::updating(function ($folder) {
            if ($folder->isDirty('name')) {
                $folder->slug = $folder->generateSlug();
            }
        });
    }
}

