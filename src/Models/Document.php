<?php

namespace Afterburner\Documents\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'folder_id',
        'name',
        'filename',
        'mime_type',
        'size',
        'storage_path',
        'upload_status',
        'upload_progress',
        'uploaded_by',
    ];

    protected $casts = [
        'upload_status' => 'string',
        'upload_progress' => 'integer',
        'size' => 'integer',
    ];

    /**
     * Get the team that owns the document.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class);
    }

    /**
     * Get the folder containing this document.
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Get all versions of this document.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderBy('version_number', 'desc');
    }

    /**
     * Get the user who uploaded the document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }

    /**
     * Get the current version of the document.
     */
    public function currentVersion(): ?DocumentVersion
    {
        return $this->versions()->latest('version_number')->first();
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
    public function getUrl(): string
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('r2');
        if ($disk->exists($this->storage_path)) {
            return $disk->url($this->storage_path);
        }

        return '';
    }

    /**
     * Check if the document can be deleted by a user.
     */
    public function canBeDeletedBy($user): bool
    {
        return $this->team->userHasPermission($user, 'delete_documents');
    }

    /**
     * Create a new version of the document.
     */
    public function createVersion(string $storagePath, int $size, $createdBy): DocumentVersion
    {
        $latestVersion = $this->versions()->max('version_number') ?? 0;
        $newVersionNumber = $latestVersion + 1;

        return $this->versions()->create([
            'version_number' => $newVersionNumber,
            'storage_path' => $storagePath,
            'size' => $size,
            'created_by' => $createdBy->id,
        ]);
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
     * Scope a query to filter by upload status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('upload_status', $status);
    }
}

