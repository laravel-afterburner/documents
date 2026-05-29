<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    protected $fillable = [
        'name',
        'user_id',
        'trial_ends_at',
        'documents_entitled',
        'storage_within_limit',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'documents_entitled' => 'boolean',
        'storage_within_limit' => 'boolean',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function userHasPermission($user, string $permission): bool
    {
        return true;
    }
}
