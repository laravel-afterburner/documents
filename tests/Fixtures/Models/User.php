<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password'];

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class);
    }

    public function belongsToTeam($team): bool
    {
        if ($team === null) {
            return false;
        }

        $teamId = is_object($team) ? $team->id : $team;

        return $this->teams()->where('teams.id', $teamId)->exists();
    }

    public function can($ability, $arguments = []): bool
    {
        return true;
    }
}
