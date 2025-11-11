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
        'notes',
        'filename',
        'mime_type',
        'size',
        'storage_path',
        'upload_status',
        'upload_progress',
        'uploaded_by',
        'retention_tag_id',
        'retention_expires_at',
    ];

    protected $casts = [
        'upload_status' => 'string',
        'upload_progress' => 'integer',
        'size' => 'integer',
        'retention_expires_at' => 'datetime',
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
     * Get the retention tag for this document.
     */
    public function retentionTag(): BelongsTo
    {
        return $this->belongsTo(RetentionTag::class);
    }

    /**
     * Check if the document is protected by retention.
     */
    public function isRetentionProtected(): bool
    {
        if (!$this->retention_tag_id || !$this->retention_expires_at) {
            return false;
        }

        return now()->isBefore($this->retention_expires_at);
    }

    /**
     * Check if the document can be deleted (not protected by retention).
     */
    public function canBeDeleted(): bool
    {
        return !$this->isRetentionProtected();
    }

    /**
     * Get the current version of the document.
     */
    public function currentVersion(): ?DocumentVersion
    {
        return $this->versions()->latest('version_number')->first();
    }

    /**
     * Get the current version number of the document.
     */
    public function getCurrentVersionNumber(): ?int
    {
        $currentVersion = $this->currentVersion();
        return $currentVersion ? $currentVersion->version_number : null;
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

    /**
     * Get the timezone for this document's team, or fall back to app timezone.
     */
    public function getTimezone(): string
    {
        $team = $this->team;
        
        if ($team && isset($team->timezone) && !empty($team->timezone)) {
            return $team->timezone;
        }
        
        if ($team && isset($team->time_zone) && !empty($team->time_zone)) {
            return $team->time_zone;
        }
        
        return config('app.timezone', 'UTC');
    }

    /**
     * Format the created_at timestamp in the team's timezone.
     */
    public function getFormattedCreatedAt(string $format = 'M d, Y'): string
    {
        return $this->created_at->setTimezone($this->getTimezone())->format($format);
    }

    /**
     * Get the appropriate icon SVG for the document based on its MIME type.
     *
     * @param string $size Tailwind size class (e.g., 'w-8 h-8', 'w-6 h-6')
     */
    public function getIconSvg(string $size = 'w-8 h-8'): string
    {
        $mimeType = $this->mime_type ?? '';
        $extension = strtolower(pathinfo($this->filename ?? '', PATHINFO_EXTENSION));

        // PDF
        if ($mimeType === 'application/pdf' || $extension === 'pdf') {
            return '<svg class="' . $size . ' text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>';
        }

        // Images
        if (str_starts_with($mimeType, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'])) {
            return '<svg class="' . $size . ' text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>';
        }

        // Word Documents
        if (str_contains($mimeType, 'wordprocessingml') || str_contains($mimeType, 'msword') || in_array($extension, ['doc', 'docx'])) {
            return '<svg class="' . $size . ' text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
        }

        // Excel Spreadsheets
        if (str_contains($mimeType, 'spreadsheetml') || str_contains($mimeType, 'ms-excel') || in_array($extension, ['xls', 'xlsx', 'csv'])) {
            return '<svg class="' . $size . ' text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
        }

        // PowerPoint Presentations
        if (str_contains($mimeType, 'presentationml') || str_contains($mimeType, 'ms-powerpoint') || in_array($extension, ['ppt', 'pptx'])) {
            return '<svg class="' . $size . ' text-orange-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
        }

        // Text Files
        if (str_starts_with($mimeType, 'text/') || in_array($extension, ['txt', 'md', 'rtf'])) {
            return '<svg class="' . $size . ' text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
        }

        // Code Files
        if (in_array($extension, ['js', 'jsx', 'ts', 'tsx', 'php', 'py', 'java', 'cpp', 'c', 'cs', 'rb', 'go', 'rs', 'swift', 'kt', 'html', 'css', 'scss', 'sass', 'less', 'xml', 'json', 'yaml', 'yml', 'sh', 'bash', 'zsh', 'sql'])) {
            return '<svg class="' . $size . ' text-purple-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>';
        }

        // Archives
        if (str_starts_with($mimeType, 'application/zip') || str_starts_with($mimeType, 'application/x-') || in_array($extension, ['zip', 'rar', '7z', 'tar', 'gz', 'bz2'])) {
            return '<svg class="' . $size . ' text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>';
        }

        // Video Files
        if (str_starts_with($mimeType, 'video/') || in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', 'm4v'])) {
            return '<svg class="' . $size . ' text-pink-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>';
        }

        // Audio Files
        if (str_starts_with($mimeType, 'audio/') || in_array($extension, ['mp3', 'wav', 'flac', 'aac', 'ogg', 'wma', 'm4a'])) {
            return '<svg class="' . $size . ' text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg>';
        }

        // Default document icon
        return '<svg class="' . $size . ' text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
    }
}

