<?php

namespace Afterburner\Documents\Tests\Unit;

use Afterburner\Documents\Models\TeamDocumentSetting;
use Afterburner\Documents\Support\DocumentRetentionSettings;
use Afterburner\Documents\Tests\TestCase;

class DocumentRetentionSettingsTest extends TestCase
{
    public function test_uses_config_default_when_no_team_setting_exists(): void
    {
        config(['afterburner-documents.retention.enabled' => true]);

        [, $team] = $this->createTeamWithUser();

        $this->assertTrue(DocumentRetentionSettings::enabledForTeam($team));
    }

    public function test_respects_global_config_kill_switch(): void
    {
        config(['afterburner-documents.retention.enabled' => false]);

        [, $team] = $this->createTeamWithUser();

        TeamDocumentSetting::query()->create([
            'team_id' => $team->id,
            'retention_tags_enabled' => true,
        ]);

        $this->assertFalse(DocumentRetentionSettings::enabledForTeam($team));
    }

    public function test_respects_per_team_setting(): void
    {
        config(['afterburner-documents.retention.enabled' => true]);

        [, $team] = $this->createTeamWithUser();

        TeamDocumentSetting::query()->create([
            'team_id' => $team->id,
            'retention_tags_enabled' => false,
        ]);

        $this->assertFalse(DocumentRetentionSettings::enabledForTeam($team));
    }

    public function test_for_team_creates_settings_record(): void
    {
        [, $team] = $this->createTeamWithUser();

        $setting = DocumentRetentionSettings::forTeam($team);

        $this->assertDatabaseHas('team_document_settings', [
            'team_id' => $team->id,
            'retention_tags_enabled' => true,
        ]);
        $this->assertSame($team->id, $setting->team_id);
    }
}
