<?php

namespace Afterburner\Documents\Livewire\Documents;

use Afterburner\Documents\Models\Document;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DocumentViewer extends Component
{
    public Document $document;
    public bool $showing = false;

    public function mount(Document $document)
    {
        $this->document = $document;

        // Check permission
        if (!Auth::user()->can('view', $document)) {
            abort(403, 'Access denied.');
        }
    }

    public function open()
    {
        $this->showing = true;
    }

    public function close()
    {
        $this->showing = false;
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

        return response()->streamDownload(function () use ($disk) {
            echo $disk->get($this->document->storage_path);
        }, $this->document->filename, [
            'Content-Type' => $this->document->mime_type,
        ]);
    }

    public function render()
    {
        $versions = $this->document->versions()->with('creator')->get();

        return view('afterburner-documents::documents.document-viewer', [
            'versions' => $versions,
        ]);
    }
}

