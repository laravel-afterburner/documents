<?php

namespace Afterburner\Documents\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'folder_id',
        'name',
        'filename',
        'original_filename',
        'mime_type',
        'file_size',
        'storage_path',
        'storage_disk',
        'version',
        'created_by',
        'updated_by',
        'retention_tag_id',
        'retention_expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'retention_expires_at' => 'datetime',
        'file_size' => 'integer',
        'version' => 'integer',
    ];

    /**
     * Get the team that owns the document.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class);
    }

    /**
     * Get the folder that contains the document.
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Get all versions of the document.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderBy('version_number', 'desc');
    }

    /**
     * Get the latest version of the document.
     */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(DocumentVersion::class)
            ->where('version_number', $this->version)
            ->latest('version_number');
    }

    /**
     * Get the current version record.
     */
    public function currentVersion(): ?DocumentVersion
    {
        return $this->versions()->where('version_number', $this->version)->first();
    }

    /**
     * Get the user who created the document.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who last updated the document.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Get the permissions for the document.
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(DocumentPermission::class);
    }

    /**
     * Get the retention tag for the document.
     */
    public function retentionTag(): BelongsTo
    {
        return $this->belongsTo(RetentionTag::class);
    }

    /**
     * Get the audit trail for the document.
     */
    public function audits(): HasMany
    {
        return $this->hasMany(DocumentAudit::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the storage path for the document.
     */
    public function getStoragePath(): string
    {
        return $this->storage_path;
    }

    /**
     * Get the URL for the document.
     */
    public function getUrl(): ?string
    {
        if (!Storage::disk($this->storage_disk)->exists($this->storage_path)) {
            return null;
        }

        return Storage::disk($this->storage_disk)->url($this->storage_path);
    }

    /**
     * Get a temporary URL for the document.
     */
    public function getTemporaryUrl(int $expirationMinutes = 60): ?string
    {
        try {
            return Storage::disk($this->storage_disk)->temporaryUrl(
                $this->storage_path,
                now()->addMinutes($expirationMinutes)
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if a user can view the document.
     */
    public function canView($user): bool
    {
        // Check team membership
        if (!$this->team->hasUser($user)) {
            return false;
        }

        // Check folder permissions if document is in a folder (cascading permissions)
        if ($this->folder_id && $this->folder) {
            if (!$this->folder->canView($user)) {
                return false;
            }
        }

        // Check document-specific permissions (override folder permissions)
        $userRoles = $user->roles()->where('team_id', $this->team_id)->pluck('slug')->toArray();

        $permission = $this->permissions()
            ->whereIn('role_slug', $userRoles)
            ->where('can_view', true)
            ->first();

        // If document has explicit permissions, use those; otherwise inherit from folder
        if ($this->permissions()->count() > 0) {
            return $permission !== null;
        }

        // If no explicit document permissions, inherit from folder
        return $this->folder_id && $this->folder ? $this->folder->canView($user) : true;
    }

    /**
     * Check if a user can edit the document.
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

        // Check folder permissions if document is in a folder
        if ($this->folder_id && $this->folder) {
            if (!$this->folder->canEdit($user)) {
                return false;
            }
        }

        $userRoles = $user->roles()->where('team_id', $this->team_id)->pluck('slug')->toArray();

        $permission = $this->permissions()
            ->whereIn('role_slug', $userRoles)
            ->where('can_edit', true)
            ->first();

        // If document has explicit permissions, use those; otherwise inherit from folder
        if ($this->permissions()->count() > 0) {
            return $permission !== null;
        }

        // If no explicit document permissions, inherit from folder
        return $this->folder_id && $this->folder ? $this->folder->canEdit($user) : false;
    }

    /**
     * Check if a user can delete the document.
     */
    public function canDelete($user): bool
    {
        if (!$this->team->hasUser($user)) {
            return false;
        }

        // Team owners always have delete permission
        if ($this->isTeamOwner($user)) {
            return true;
        }

        // Check folder permissions if document is in a folder
        if ($this->folder_id && $this->folder) {
            if (!$this->folder->canDelete($user)) {
                return false;
            }
        }

        $userRoles = $user->roles()->where('team_id', $this->team_id)->pluck('slug')->toArray();

        $permission = $this->permissions()
            ->whereIn('role_slug', $userRoles)
            ->where('can_delete', true)
            ->first();

        // If document has explicit permissions, use those; otherwise inherit from folder
        if ($this->permissions()->count() > 0) {
            return $permission !== null;
        }

        // If no explicit document permissions, inherit from folder
        return $this->folder_id && $this->folder ? $this->folder->canDelete($user) : false;
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
     * Create a new version of the document.
     */
    public function createVersion(string $storagePath, int $fileSize, string $mimeType, $userId, ?string $changeSummary = null): DocumentVersion
    {
        $newVersionNumber = $this->version + 1;

        $version = DocumentVersion::create([
            'document_id' => $this->id,
            'version_number' => $newVersionNumber,
            'filename' => $this->filename,
            'storage_path' => $storagePath,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'created_by' => $userId,
            'change_summary' => $changeSummary,
        ]);

        $this->update([
            'version' => $newVersionNumber,
            'updated_by' => $userId,
        ]);

        return $version;
    }

    /**
     * Get human-readable file size.
     */
    public function getFileSizeHuman(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Scope a query to only include documents for a specific team.
     */
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope a query to only include documents in a specific folder.
     */
    public function scopeInFolder($query, $folderId)
    {
        return $query->where('folder_id', $folderId);
    }

    /**
     * Scope a query to only include documents with a specific MIME type.
     */
    public function scopeByMimeType($query, $mimeType)
    {
        return $query->where('mime_type', $mimeType);
    }

    /**
     * Scope a query to only include documents with retention tags.
     */
    public function scopeWithRetention($query)
    {
        return $query->whereNotNull('retention_tag_id');
    }

    /**
     * Check if document has expired retention.
     */
    public function hasExpiredRetention(): bool
    {
        if (!$this->retention_expires_at) {
            return false;
        }

        return $this->retention_expires_at->isPast();
    }

    /**
     * Get the file extension from the filename.
     */
    public function getExtension(): string
    {
        return pathinfo($this->filename, PATHINFO_EXTENSION);
    }

    /**
     * Check if document is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if document is a PDF.
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }
}

