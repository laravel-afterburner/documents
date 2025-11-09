<?php

namespace Afterburner\Documents\Livewire\Documents;

use Afterburner\Documents\Actions\DeleteDocument;
use Afterburner\Documents\Actions\UpdateDocument;
use Afterburner\Documents\Models\Document;
use App\Traits\InteractsWithBanner;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class DocumentViewer extends Component
{
    use InteractsWithBanner;

    public Document $document;
    public bool $showing = false;
    public bool $autoOpen = false;

    // Edit modal
    public bool $showingEditModal = false;
    public $documentName = '';
    public $newFile = null;

    // Delete modal
    public bool $showingDeleteModal = false;

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
        $this->newFile = null;
        $this->showingEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showingEditModal = false;
        $this->reset(['documentName', 'newFile']);
    }

    public function updateDocument()
    {
        if (!Auth::user()->can('update', $this->document)) {
            abort(403, 'Access denied.');
        }

        $this->validate([
            'documentName' => 'required|string|max:255',
            'newFile' => 'nullable|file|max:'.config('afterburner-documents.upload.max_file_size', 2147483648),
        ]);

        try {
            $attributes = ['name' => $this->documentName];
            $fileContent = null;

            // If new file uploaded, get its content
            if ($this->newFile instanceof TemporaryUploadedFile) {
                $fileContent = file_get_contents($this->newFile->getRealPath());
                $attributes['filename'] = $this->newFile->getClientOriginalName();
                $attributes['mime_type'] = $this->newFile->getMimeType();
                $attributes['size'] = $this->newFile->getSize();
            }

            app(UpdateDocument::class)->execute(
                $this->document,
                $attributes,
                $fileContent,
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

