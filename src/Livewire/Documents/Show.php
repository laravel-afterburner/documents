<?php

namespace Afterburner\Documents\Livewire\Documents;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\DocumentPermission;
use Afterburner\Documents\Models\DocumentVersion;
use Afterburner\Documents\Services\DocumentStorageService;
use Livewire\Component;

class Show extends Component
{
    public Document $document;
    public $previewUrl = null;
    public $activeTab = 'details';
    public $showPermissionsModal = false;
    public $showVersionsModal = false;

    // Permissions properties
    public $permissions = [];
    public $availableRoles = [];

    // Versions properties
    public $versions = [];
    public $versionToRestore = null;
    public $showRestoreConfirm = false;
    public $showDeleteConfirm = false;

    protected $listeners = ['refreshDocument' => '$refresh'];

    public function mount(Document $document)
    {
        $this->authorize('view', $document);
        $this->document = $document->load(['folder', 'creator', 'updater', 'retentionTag', 'permissions', 'versions.creator']);
    }

    public function download()
    {
        $this->authorize('download', $this->document);
        return redirect()->route('documents.download', $this->document->id);
    }

    public function getPreviewUrl()
    {
        $this->authorize('view', $this->document);

        if (!$this->document->isImage() && !$this->document->isPdf()) {
            $this->addError('preview', 'Preview not available for this file type.');
            return;
        }

        $storageService = app(DocumentStorageService::class);
        $this->previewUrl = $storageService->getTemporaryUrl($this->document, 60);

        if (!$this->previewUrl) {
            $this->addError('preview', 'Failed to generate preview URL.');
        }
    }

    public function confirmDelete()
    {
        $this->authorize('delete', $this->document);
        $this->showDeleteConfirm = true;
    }

    public function delete()
    {
        $this->authorize('delete', $this->document);
        
        $this->document->delete();
        
        $this->showDeleteConfirm = false;
        session()->flash('message', 'Document deleted successfully.');
        return redirect()->route('documents.index');
    }

    public function openPermissionsModal()
    {
        $this->authorize('update', $this->document);
        
        // Load permissions
        $this->permissions = $this->document->permissions->keyBy('role_slug')->toArray();

        // Get available roles from team
        $team = $this->document->team;
        $this->availableRoles = $team->roles()->pluck('slug', 'name')->toArray();
        
        $this->showPermissionsModal = true;
    }

    public function closePermissionsModal()
    {
        $this->showPermissionsModal = false;
        $this->document->refresh();
    }

    public function updatePermission($roleSlug, $permission, $value)
    {
        $this->authorize('update', $this->document);

        if (!isset($this->permissions[$roleSlug])) {
            // Create new permission
            DocumentPermission::create([
                'document_id' => $this->document->id,
                'role_slug' => $roleSlug,
                $permission => $value,
            ]);
        } else {
            // Update existing permission
            $perm = DocumentPermission::findOrFail($this->permissions[$roleSlug]['id']);
            $perm->update([$permission => $value]);
        }

        // Refresh permissions
        $this->document->refresh();
        $this->permissions = $this->document->permissions->keyBy('role_slug')->toArray();

        // Log audit trail
        \Afterburner\Documents\Models\DocumentAudit::logAction(
            $this->document,
            auth()->user(),
            'permission_changed',
            [
                'role_slug' => $roleSlug,
                'permission' => $permission,
                'value' => $value,
            ]
        );
    }

    public function removePermission($roleSlug)
    {
        $this->authorize('update', $this->document);

        if (isset($this->permissions[$roleSlug])) {
            $perm = DocumentPermission::findOrFail($this->permissions[$roleSlug]['id']);
            $perm->delete();

            // Refresh permissions
            $this->document->refresh();
            $this->permissions = $this->document->permissions->keyBy('role_slug')->toArray();

            // Log audit trail
            \Afterburner\Documents\Models\DocumentAudit::logAction(
                $this->document,
                auth()->user(),
                'permission_changed',
                [
                    'action' => 'removed',
                    'role_slug' => $roleSlug,
                ]
            );
        }
    }

    public function openVersionsModal()
    {
        $this->authorize('view', $this->document);
        
        $this->versions = $this->document->versions()
            ->with('creator')
            ->orderBy('version_number', 'desc')
            ->get()
            ->toArray();
        
        $this->showVersionsModal = true;
    }

    public function closeVersionsModal()
    {
        $this->showVersionsModal = false;
    }

    public function confirmRestore($versionId)
    {
        $this->authorize('update', $this->document);

        $version = DocumentVersion::findOrFail($versionId);

        // Verify version belongs to document
        if ($version->document_id !== $this->document->id) {
            $this->addError('restore', 'Invalid version.');
            return;
        }

        $this->versionToRestore = $version;
        $this->showRestoreConfirm = true;
    }

    public function restore()
    {
        $this->authorize('update', $this->document);

        if (!$this->versionToRestore) {
            return;
        }

        // Create backup version before restoring
        if (config('afterburner-documents.versioning.enabled', true) &&
            config('afterburner-documents.versioning.auto_version_on_update', true)) {
            $this->document->createVersion(
                $this->document->storage_path,
                $this->document->file_size,
                $this->document->mime_type,
                auth()->id(),
                'Backup before restoring to version '.$this->versionToRestore->version_number
            );
        }

        // Restore version
        $this->document->update([
            'storage_path' => $this->versionToRestore->storage_path,
            'filename' => $this->versionToRestore->filename,
            'file_size' => $this->versionToRestore->file_size,
            'mime_type' => $this->versionToRestore->mime_type,
            'version' => $this->document->version + 1,
            'updated_by' => auth()->id(),
        ]);

        // Create new version record for restored version
        DocumentVersion::create([
            'document_id' => $this->document->id,
            'version_number' => $this->document->version,
            'filename' => $this->versionToRestore->filename,
            'storage_path' => $this->versionToRestore->storage_path,
            'file_size' => $this->versionToRestore->file_size,
            'mime_type' => $this->versionToRestore->mime_type,
            'created_by' => auth()->id(),
            'change_summary' => 'Restored from version '.$this->versionToRestore->version_number,
        ]);

        // Log audit trail
        \Afterburner\Documents\Models\DocumentAudit::logAction(
            $this->document,
            auth()->user(),
            'updated',
            [
                'action' => 'restored_version',
                'restored_version' => $this->versionToRestore->version_number,
            ]
        );

        // Refresh versions
        $this->document->refresh();
        $this->versions = $this->document->versions()
            ->with('creator')
            ->orderBy('version_number', 'desc')
            ->get()
            ->toArray();

        $restoredVersionNumber = $this->versionToRestore->version_number;
        $this->showRestoreConfirm = false;
        $this->versionToRestore = null;
        $this->showVersionsModal = false;
        $this->dispatch('documentUpdated', $this->document->id);
        session()->flash('message', 'Document restored to version '.$restoredVersionNumber.'.');
    }

    public function cancelRestore()
    {
        $this->showRestoreConfirm = false;
        $this->versionToRestore = null;
    }

    public function downloadVersion($versionId)
    {
        $this->authorize('view', $this->document);

        $version = DocumentVersion::findOrFail($versionId);

        // Verify version belongs to document
        if ($version->document_id !== $this->document->id) {
            abort(404);
        }

        return redirect()->route('documents.versions.download', [
            'document' => $this->document->id,
            'version' => $version->id,
        ]);
    }

    public function render()
    {
        return view('afterburner-documents::documents.show');
    }
}

