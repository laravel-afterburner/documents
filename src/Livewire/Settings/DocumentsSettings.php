<?php

namespace Afterburner\Documents\Livewire\Settings;

use Afterburner\Documents\Support\TeamDocumentSettings;
use App\Models\Team;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class DocumentsSettings extends Component
{
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

    public function save(): void
    {
        Gate::authorize('update', $this->team);

        $validated = $this->validate([
            'retentionTagsEnabled' => 'boolean',
            'versioningEnabled' => 'boolean',
        ]);

        TeamDocumentSettings::forTeam($this->team)->update([
            'retention_tags_enabled' => $validated['retentionTagsEnabled'],
            'versioning_enabled' => $validated['versioningEnabled'],
        ]);

        $this->dispatch('saved');
    }

    public function render()
    {
        return view('afterburner-documents::settings.documents-settings');
    }
}
