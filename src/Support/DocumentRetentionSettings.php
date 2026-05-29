<?php

namespace Afterburner\Documents\Support;

use Afterburner\Documents\Models\TeamDocumentSetting;
use App\Models\Team;

/**
 * @deprecated Use TeamDocumentSettings::retentionEnabledForTeam() instead.
 */
class DocumentRetentionSettings
{
    public static function enabledForTeam(Team|int $team): bool
    {
        return TeamDocumentSettings::retentionEnabledForTeam($team);
    }

    public static function forTeam(Team|int $team): TeamDocumentSetting
    {
        return TeamDocumentSettings::forTeam($team);
    }
}
