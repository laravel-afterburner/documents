<?php

namespace Afterburner\Documents\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RetentionTag extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'name',
        'slug',
        'description',
        'retention_period_days',
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'retention_period_days' => 'integer',
        'is_default' => 'boolean',
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
     * Check if the retention period has expired for a given date.
     */
    public function isExpired(?\DateTime $date = null): bool
    {
        $date = $date ?? now();
        $expirationDate = $this->getExpirationDate($date);
        
        return $expirationDate->isPast();
    }

    /**
     * Get the expiration date based on the retention period.
     */
    public function getExpirationDate(?\DateTime $startDate = null): \Carbon\Carbon
    {
        $startDate = $startDate ? \Carbon\Carbon::parse($startDate) : now();
        
        return $startDate->copy()->addDays($this->retention_period_days);
    }

    /**
     * Scope a query to only include retention tags for a specific team.
     */
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope a query to only include default retention tags.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}

