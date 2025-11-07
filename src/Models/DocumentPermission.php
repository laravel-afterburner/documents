<?php

namespace Afterburner\Documents\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentPermission extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'document_id',
        'role_slug',
        'can_view',
        'can_edit',
        'can_delete',
        'can_share',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'can_view' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
        'can_share' => 'boolean',
    ];

    /**
     * Get the document that owns this permission.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Check if this permission allows a specific action.
     */
    public function hasPermission(string $action): bool
    {
        return match ($action) {
            'view' => $this->can_view,
            'edit' => $this->can_edit,
            'delete' => $this->can_delete,
            'share' => $this->can_share,
            default => false,
        };
    }
}

