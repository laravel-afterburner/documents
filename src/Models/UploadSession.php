<?php

namespace Afterburner\Documents\Models;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadSession extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'team_id',
        'user_id',
        'folder_id',
        'document_id',
        'filename',
        'mime_type',
        'total_size',
        'bytes_received',
        'storage_path',
        'notes',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'total_size' => 'integer',
        'bytes_received' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeAbandonable($query)
    {
        return $query->whereIn('status', ['uploading', 'failed', 'processing'])
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '<=', now());
            });
    }

    public function uploadProgress(): int
    {
        if ($this->total_size <= 0) {
            return 0;
        }

        return min(100, (int) floor(($this->bytes_received / $this->total_size) * 100));
    }
}
