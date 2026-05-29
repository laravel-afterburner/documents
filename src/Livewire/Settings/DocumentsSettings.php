<?php

namespace Afterburner\Documents\Livewire\Settings;

use Afterburner\Documents\Support\TeamDocumentSettings;
use App\Models\Team;
use App\Traits\InteractsWithBanner;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class DocumentsSettings extends Component
{
    use InteractsWithBanner;

    public Team $team;

    public bool $retentionTagsEnabled = true;

    public bool $versioningEnabled = true;

    public function mount(Team $team): void
    {
        $this->team = $team;

        Gate::authorize('update', $team);

        $this->retentionTagsEnabled = TeamDocumentSettings::retentionEnabledForTeam($team);
        $this->versioningEnabled = TeamDocumentSettings::versioningEnabledForTeam($team);
    }

    public function updatedRetentionTagsEnabled(bool $value): void
    {
        Gate::authorize('update', $this->team);

        $settings = TeamDocumentSettings::forTeam($this->team);
        $settings->update(['retention_tags_enabled' => $value]);

        $this->banner($value
            ? __('Retention tags enabled for this team.')
            : __('Retention tags disabled for this team.'));
    }

    public function updatedVersioningEnabled(bool $value): void
    {
        Gate::authorize('update', $this->team);

        $settings = TeamDocumentSettings::forTeam($this->team);
        $settings->update(['versioning_enabled' => $value]);

        $this->banner($value
            ? __('Version control enabled for this team.')
            : __('Version control disabled for this team.'));
    }

    public function render()
    {
        return view('afterburner-documents::settings.documents-settings');
    }
}
