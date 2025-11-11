<?php

namespace Afterburner\Documents\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RetentionTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'slug',
        'description',
        'retention_period_days',
        'color',
        'created_by',
    ];

    protected $casts = [
        'retention_period_days' => 'integer',
    ];

    /**
     * Get the team that owns the retention tag.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class);
    }

    /**
     * Get the documents with this retention tag.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get the user who created the retention tag.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Generate a unique slug for the retention tag.
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
     * Check if a retention tag is expired for a given date.
     */
    public function isExpiredFor(\DateTimeInterface $date): bool
    {
        $expiresAt = $date->modify("+{$this->retention_period_days} days");
        return now()->isAfter($expiresAt);
    }

    /**
     * Scope a query to only include retention tags for a specific team.
     */
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = $tag->generateSlug();
            }
        });

        static::updating(function ($tag) {
            if ($tag->isDirty('name')) {
                $tag->slug = $tag->generateSlug();
            }
        });
    }
}

