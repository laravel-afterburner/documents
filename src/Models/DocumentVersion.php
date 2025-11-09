<?php

namespace Afterburner\Documents\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'version_number',
        'storage_path',
        'size',
        'created_by',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'size' => 'integer',
    ];

    /**
     * Get the document this version belongs to.
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
    public function getUrl(): string
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('r2');
        if ($disk->exists($this->storage_path)) {
            return $disk->url($this->storage_path);
        }

        return '';
    }

    /**
     * Get the timezone for this version's document's team, or fall back to app timezone.
     */
    public function getTimezone(): string
    {
        $document = $this->document;
        
        if (!$document) {
            return config('app.timezone', 'UTC');
        }
        
        $team = $document->team;
        
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
    public function getFormattedCreatedAt(string $format = 'Y-m-d H:i'): string
    {
        return $this->created_at->setTimezone($this->getTimezone())->format($format);
    }
}

