<?php

namespace App\Models;

use Afterburner\Subscriptions\Concerns\HasSubscriptions;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SubscribableTeam extends Team
{
    use HasSubscriptions;

    protected $table = 'teams';

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user', 'team_id', 'user_id');
    }
}
