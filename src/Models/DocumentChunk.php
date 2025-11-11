<?php

namespace Afterburner\Documents\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'chunk_id',
        'document_id',
        'chunk_index',
        'storage_path',
        'size',
        'expires_at',
    ];

    protected $casts = [
        'chunk_index' => 'integer',
        'size' => 'integer',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the document this chunk belongs to.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Scope a query to only include chunks for a specific document.
     */
    public function scopeForDocument($query, $documentId)
    {
        return $query->where('document_id', $documentId);
    }

    /**
     * Scope a query to only include expired chunks.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }
}

