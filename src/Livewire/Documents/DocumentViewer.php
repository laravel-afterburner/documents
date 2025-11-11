<?php

namespace Afterburner\Documents\Livewire\Documents;

use Afterburner\Documents\Actions\DeleteDocument;
use Afterburner\Documents\Actions\RestoreDocumentVersion;
use Afterburner\Documents\Actions\UpdateDocument;
use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\DocumentVersion;
use App\Traits\InteractsWithBanner;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\LivewireFilepond\WithFilePond;

class DocumentViewer extends Component
{
    use InteractsWithBanner, WithFilePond;

    public Document $document;
    public bool $showing = false;
    public bool $autoOpen = false;

    // Edit modal
    public bool $showingEditModal = false;
    public $documentName = '';
    public $documentNotes = '';
    public $newFile = null;

    // Delete modal
    public bool $showingDeleteModal = false;

    // Restore version modal
    public bool $showingRestoreVersionModal = false;
    public ?DocumentVersion $versionToRestore = null;

    public function mount(Document $document, bool $autoOpen = false)
    {
        $this->document = $document;
        $this->autoOpen = $autoOpen;

        // Check permission
        if (!Auth::user()->can('view', $document)) {
            abort(403, 'Access denied.');
        }

        if ($autoOpen) {
            $this->showing = true;
        }
    }

    public function open()
    {
        $this->showing = true;
    }

    public function close()
    {
        $this->showing = false;
        $this->dispatch('document-viewer-closed');
    }

    public function download()
    {
        if (!Auth::user()->can('download', $this->document)) {
            abort(403, 'Access denied.');
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('r2');
        if (!$disk->exists($this->document->storage_path)) {
            $this->dispatch('banner-message',
                style: 'danger',
                message: __('Document file not found.'),
            );
            return;
        }

        // Close the modal
        $this->close();

        return response()->streamDownload(function () use ($disk) {
            echo $disk->get($this->document->storage_path);
        }, $this->document->filename, [
            'Content-Type' => $this->document->mime_type,
        ]);
    }

    public function openEditModal()
    {
        if (!Auth::user()->can('update', $this->document)) {
            abort(403, 'Access denied.');
        }

        $this->documentName = $this->document->name;
        $this->documentNotes = $this->document->notes ?? '';
        $this->newFile = null;
        $this->showingEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showingEditModal = false;
        $this->reset(['documentName', 'documentNotes', 'newFile']);
    }

    /**
     * Validate uploaded file (called by Spatie FilePond package).
     * This follows the exact pattern from Spatie's README.
     */
    public function validateUploadedFile($response = null): bool
    {
        $maxFileSize = config('afterburner-documents.upload.max_file_size', 2147483648);
        $allowedMimeTypes = config('afterburner-documents.upload.allowed_mime_types', []);

        $rules = [
            'documentName' => 'required|string|max:255',
            'documentNotes' => 'nullable|string|max:5000',
            'newFile' => [
                'nullable',
                'file',
                'max:'.$maxFileSize,
            ],
        ];

        if (!empty($allowedMimeTypes)) {
            $rules['newFile'][] = 'mimetypes:'.implode(',', $allowedMimeTypes);
        }

        $this->validate($rules);

        return true;
    }

    /**
     * Process uploaded file after it's uploaded via Livewire.
     * This is called automatically when newFile property is updated (after upload completes).
     * This follows Spatie's intended pattern - process files in updated* method.
     */
    public function updatedNewFile()
    {
        if (!$this->newFile || !($this->newFile instanceof TemporaryUploadedFile)) {
            return;
        }

        if (!Auth::user()->can('update', $this->document)) {
            abort(403, 'Access denied.');
        }

        // Ensure values are strings, not arrays
        $this->documentName = is_array($this->documentName) ? '' : (string) $this->documentName;
        $this->documentNotes = is_array($this->documentNotes) ? '' : ($this->documentNotes ? (string) $this->documentNotes : '');

        try {
            $attributes = [
                'name' => $this->documentName,
                'notes' => !empty($this->documentNotes) ? $this->documentNotes : null,
            ];
            $fileContent = file_get_contents($this->newFile->getRealPath());
            $attributes['filename'] = $this->newFile->getClientOriginalName();
            $attributes['mime_type'] = $this->newFile->getMimeType();
            $attributes['size'] = $this->newFile->getSize();

            app(UpdateDocument::class)->execute(
                $this->document,
                $attributes,
                $fileContent,
                Auth::user()
            );

            // Refresh document
            $this->document->refresh();

            // Reset file after processing
            $this->reset(['newFile']);

            // Close modal after processing
            $this->closeEditModal();

            $this->banner(__('Document updated successfully.'));
            $this->dispatch('document-updated');
        } catch (\Exception $e) {
            $this->dangerBanner($e->getMessage());
            $this->reset(['newFile']);
        }
    }

    public function updateDocument()
    {
        if (!Auth::user()->can('update', $this->document)) {
            abort(403, 'Access denied.');
        }

        // Ensure values are strings, not arrays
        $this->documentName = is_array($this->documentName) ? '' : (string) $this->documentName;
        $this->documentNotes = is_array($this->documentNotes) ? '' : ($this->documentNotes ? (string) $this->documentNotes : '');

        $this->validate([
            'documentName' => 'required|string|max:255',
            'documentNotes' => 'nullable|string|max:5000',
        ]);

        // Only update name if no file was uploaded (file upload is handled by updatedNewFile)
        if ($this->newFile instanceof TemporaryUploadedFile) {
            // File upload is handled automatically by updatedNewFile
            return;
        }

        try {
            $attributes = [
                'name' => $this->documentName,
                'notes' => !empty($this->documentNotes) ? $this->documentNotes : null,
            ];

            app(UpdateDocument::class)->execute(
                $this->document,
                $attributes,
                null, // No file content
                Auth::user()
            );

            // Refresh document
            $this->document->refresh();

            $this->banner(__('Document updated successfully.'));
            $this->closeEditModal();
            $this->dispatch('document-updated');
        } catch (\Exception $e) {
            $this->dangerBanner($e->getMessage());
        }
    }

    public function confirmDelete()
    {
        if (!Auth::user()->can('delete', $this->document)) {
            abort(403, 'Access denied.');
        }

        $this->showingDeleteModal = true;
    }

    public function cancelDelete()
    {
        $this->showingDeleteModal = false;
    }

    public function deleteDocument()
    {
        if (!Auth::user()->can('delete', $this->document)) {
            abort(403, 'Access denied.');
        }

        try {
            app(DeleteDocument::class)->execute(
                $this->document,
                Auth::user(),
                false // Soft delete
            );

            $this->banner(__('Document deleted successfully.'));
            $this->showingDeleteModal = false;
            $this->close();
            $this->dispatch('document-deleted');
        } catch (\Exception $e) {
            $this->dangerBanner($e->getMessage());
        }
    }

    public function confirmRestoreVersion(DocumentVersion $version)
    {
        if (!Auth::user()->can('restoreVersion', $this->document)) {
            abort(403, 'Access denied.');
        }

        $this->versionToRestore = $version;
        $this->showingRestoreVersionModal = true;
    }

    public function cancelRestoreVersion()
    {
        $this->showingRestoreVersionModal = false;
        $this->versionToRestore = null;
    }

    public function restoreVersion()
    {
        if (!$this->versionToRestore) {
            return;
        }

        if (!Auth::user()->can('restoreVersion', $this->document)) {
            abort(403, 'Access denied.');
        }

        try {
            app(RestoreDocumentVersion::class)->execute(
                $this->document,
                $this->versionToRestore,
                Auth::user()
            );

            // Refresh document to get updated data
            $this->document->refresh();

            $this->banner(__('Document version restored successfully.'));
            $this->showingRestoreVersionModal = false;
            $this->versionToRestore = null;
            $this->dispatch('document-updated');
        } catch (\Exception $e) {
            $this->dangerBanner($e->getMessage());
        }
    }

    public function render()
    {
        $versions = $this->document->versions()
            ->with(['creator', 'document.team'])
            ->get();

        return view('afterburner-documents::documents.document-viewer', [
            'versions' => $versions,
        ]);
    }
}

