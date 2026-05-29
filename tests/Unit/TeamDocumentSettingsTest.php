<?php

namespace Afterburner\Documents\Tests\Unit;

use Afterburner\Documents\Models\TeamDocumentSetting;
use Afterburner\Documents\Support\TeamDocumentSettings;
use Afterburner\Documents\Tests\TestCase;

class TeamDocumentSettingsTest extends TestCase
{
    public function test_versioning_uses_config_default_when_no_team_setting_exists(): void
    {
        config(['afterburner-documents.versioning.enabled' => true]);

        [, $team] = $this->createTeamWithUser();

        $this->assertTrue(TeamDocumentSettings::versioningEnabledForTeam($team));
    }

    public function test_versioning_respects_global_config_kill_switch(): void
    {
        config(['afterburner-documents.versioning.enabled' => false]);

        [, $team] = $this->createTeamWithUser();

        TeamDocumentSetting::query()->create([
            'team_id' => $team->id,
            'retention_tags_enabled' => true,
            'versioning_enabled' => true,
        ]);

        $this->assertFalse(TeamDocumentSettings::versioningEnabledForTeam($team));
    }

    public function test_versioning_respects_per_team_setting(): void
    {
        config(['afterburner-documents.versioning.enabled' => true]);

        [, $team] = $this->createTeamWithUser();

        TeamDocumentSetting::query()->create([
            'team_id' => $team->id,
            'retention_tags_enabled' => true,
            'versioning_enabled' => false,
        ]);

        $this->assertFalse(TeamDocumentSettings::versioningEnabledForTeam($team));
    }

    public function test_for_team_creates_settings_with_versioning_default(): void
    {
        [, $team] = $this->createTeamWithUser();

        TeamDocumentSettings::forTeam($team);

        $this->assertDatabaseHas('team_document_settings', [
            'team_id' => $team->id,
            'versioning_enabled' => true,
        ]);
    }
}
