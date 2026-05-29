<?php

namespace Afterburner\Documents\Models;

use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamDocumentSetting extends Model
{
    protected $fillable = [
        'team_id',
        'retention_tags_enabled',
        'versioning_enabled',
    ];

    protected function casts(): array
    {
        return [
            'retention_tags_enabled' => 'boolean',
            'versioning_enabled' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
