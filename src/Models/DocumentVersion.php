<?php

namespace Afterburner\Documents\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DocumentVersion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'document_id',
        'version_number',
        'filename',
        'storage_path',
        'file_size',
        'mime_type',
        'created_by',
        'change_summary',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'file_size' => 'integer',
        'version_number' => 'integer',
    ];

    /**
     * Get the document that owns this version.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the user who created this version.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the storage path for this version.
     */
    public function getStoragePath(): string
    {
        return $this->storage_path;
    }

    /**
     * Get the URL for this version.
     */
    public function getUrl(): ?string
    {
        $disk = $this->document->storage_disk ?? 'r2';
        
        if (!Storage::disk($disk)->exists($this->storage_path)) {
            return null;
        }

        return Storage::disk($disk)->url($this->storage_path);
    }

    /**
     * Get a temporary URL for this version.
     */
    public function getTemporaryUrl(int $expirationMinutes = 60): ?string
    {
        $disk = $this->document->storage_disk ?? 'r2';
        
        try {
            return Storage::disk($disk)->temporaryUrl(
                $this->storage_path,
                now()->addMinutes($expirationMinutes)
            );
        } catch (\Exception $e) {
            return null;
        }
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
}

