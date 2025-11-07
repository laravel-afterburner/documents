<?php

namespace Afterburner\Documents\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Folder extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'parent_id',
        'name',
        'slug',
        'description',
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
     * Get the permissions for the folder.
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(FolderPermission::class);
    }

    /**
     * Get the user who created the folder.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Check if a user can view the folder.
     */
    public function canView($user): bool
    {
        // Check team membership
        if (!$this->team->hasUser($user)) {
            return false;
        }

        // Team owners always have view permission
        if ($this->isTeamOwner($user)) {
            return true;
        }

        // Check parent folder permissions if this folder has a parent
        if ($this->parent_id && $this->parent) {
            if (!$this->parent->canView($user)) {
                return false;
            }
        }

        // Check folder-specific permissions
        $userRoles = $user->roles()->where('team_id', $this->team_id)->pluck('slug')->toArray();

        $permission = $this->permissions()
            ->whereIn('role_slug', $userRoles)
            ->where('can_view', true)
            ->first();

        return $permission !== null;
    }

    /**
     * Check if a user can create items in the folder.
     */
    public function canCreate($user): bool
    {
        if (!$this->team->hasUser($user)) {
            return false;
        }

        // Team owners always have create permission
        if ($this->isTeamOwner($user)) {
            return true;
        }

        // Check parent folder permissions if this folder has a parent
        if ($this->parent_id && $this->parent) {
            if (!$this->parent->canCreate($user)) {
                return false;
            }
        }

        $userRoles = $user->roles()->where('team_id', $this->team_id)->pluck('slug')->toArray();

        $permission = $this->permissions()
            ->whereIn('role_slug', $userRoles)
            ->where('can_create', true)
            ->first();

        return $permission !== null;
    }

    /**
     * Check if a user can edit the folder.
     */
    public function canEdit($user): bool
    {
        if (!$this->team->hasUser($user)) {
            return false;
        }

        // Team owners always have edit permission
        if ($this->isTeamOwner($user)) {
            return true;
        }

        $userRoles = $user->roles()->where('team_id', $this->team_id)->pluck('slug')->toArray();

        $permission = $this->permissions()
            ->whereIn('role_slug', $userRoles)
            ->where('can_edit', true)
            ->first();

        return $permission !== null;
    }

    /**
     * Check if a user can delete the folder.
     */
    public function canDelete($user): bool
    {
        if (!$this->team->hasUser($user)) {
            return false;
        }

        if (!$this->canBeDeleted()) {
            return false;
        }

        // Team owners always have delete permission
        if ($this->isTeamOwner($user)) {
            return true;
        }

        $userRoles = $user->roles()->where('team_id', $this->team_id)->pluck('slug')->toArray();

        $permission = $this->permissions()
            ->whereIn('role_slug', $userRoles)
            ->where('can_delete', true)
            ->first();

        return $permission !== null;
    }

    /**
     * Check if a user is the team owner.
     */
    protected function isTeamOwner($user): bool
    {
        // Check if team has user_id property (Laravel Jetstream pattern)
        if (isset($this->team->user_id) && $this->team->user_id === $user->id) {
            return true;
        }

        // Check if team has ownedBy method
        if (method_exists($this->team, 'ownedBy') && $this->team->ownedBy($user)) {
            return true;
        }

        // Check if user has 'owner' role for this team
        $userRoles = $user->roles()->where('team_id', $this->team_id)->pluck('slug')->toArray();
        if (in_array('owner', $userRoles)) {
            return true;
        }

        return false;
    }

    /**
     * Get the full path of the folder.
     */
    public function getPath(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' / ', $path);
    }

    /**
     * Check if this is a root folder (no parent).
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Check if the folder can be deleted (no documents or subfolders).
     */
    public function canBeDeleted(): bool
    {
        return $this->documents()->count() === 0 && $this->children()->count() === 0;
    }

    /**
     * Scope a query to only include folders for a specific team.
     */
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope a query to only include root folders.
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope a query to only include folders in a specific parent folder.
     */
    public function scopeInFolder($query, $folderId)
    {
        return $query->where('parent_id', $folderId);
    }

    /**
     * Get all descendant folders (children, grandchildren, etc.).
     */
    public function getDescendants(): \Illuminate\Support\Collection
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getDescendants());
        }

        return $descendants;
    }

    /**
     * Get all documents in this folder and all subfolders.
     */
    public function getAllDocuments(): \Illuminate\Support\Collection
    {
        $documents = $this->documents;

        foreach ($this->getDescendants() as $descendant) {
            $documents = $documents->merge($descendant->documents);
        }

        return $documents;
    }

    /**
     * Get the total size of all documents in this folder (including subfolders).
     */
    public function getTotalSize(): int
    {
        return $this->getAllDocuments()->sum('file_size');
    }

    /**
     * Get human-readable total size.
     */
    public function getTotalSizeHuman(): string
    {
        $bytes = $this->getTotalSize();
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2).' '.$units[$i];
    }
}

