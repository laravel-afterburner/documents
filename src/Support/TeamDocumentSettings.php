<?php

namespace Afterburner\Documents\Support;

use Afterburner\Documents\Models\TeamDocumentSetting;
use App\Models\Team;

class TeamDocumentSettings
{
    /**
     * Whether retention tags are enabled for the given team.
     */
    public static function retentionEnabledForTeam(Team|int $team): bool
    {
        if (! config('afterburner-documents.retention.enabled', true)) {
            return false;
        }

        $setting = static::findForTeam($team);

        if ($setting) {
            return $setting->retention_tags_enabled;
        }

        return (bool) config('afterburner-documents.retention.enabled', true);
    }

    /**
     * Whether document version control (viewer modal, history, restore) is enabled.
     */
    public static function versioningEnabledForTeam(Team|int $team): bool
    {
        if (! config('afterburner-documents.versioning.enabled', true)) {
            return false;
        }

        $setting = static::findForTeam($team);

        if ($setting) {
            return $setting->versioning_enabled;
        }

        return (bool) config('afterburner-documents.versioning.enabled', true);
    }

    /**
     * Get or create document settings for a team.
     */
    public static function forTeam(Team|int $team): TeamDocumentSetting
    {
        $teamId = $team instanceof Team ? $team->id : $team;

        return TeamDocumentSetting::query()->firstOrCreate(
            ['team_id' => $teamId],
            [
                'retention_tags_enabled' => (bool) config('afterburner-documents.retention.enabled', true),
                'versioning_enabled' => (bool) config('afterburner-documents.versioning.enabled', true),
            ]
        );
    }

    protected static function findForTeam(Team|int $team): ?TeamDocumentSetting
    {
        $teamId = $team instanceof Team ? $team->id : $team;

        return TeamDocumentSetting::query()->where('team_id', $teamId)->first();
    }
}
