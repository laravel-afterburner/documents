<?php

namespace Afterburner\Documents\Http\Controllers;

use Afterburner\Documents\Actions\CreateDocument;
use Afterburner\Documents\Actions\DeleteDocument;
use Afterburner\Documents\Actions\UpdateDocument;
use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Policies\DocumentPolicy;
use Afterburner\Documents\Services\DocumentStorageService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controller;

class DocumentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected CreateDocument $createDocument,
        protected UpdateDocument $updateDocument,
        protected DeleteDocument $deleteDocument,
        protected DocumentStorageService $storageService
    ) {
    }

    /**
     * Display a listing of documents.
     */
    public function index(Request $request, \App\Models\Team $team)
    {
        // If API request, return JSON
        if ($request->wantsJson() || $request->expectsJson()) {
            $query = Document::forTeam($team->id)
                ->with(['folder', 'creator', 'retentionTag']);

            // Filter by folder
            if ($request->has('folder_id')) {
                if ($request->folder_id === 'null' || $request->folder_id === null) {
                    $query->whereNull('folder_id');
                } else {
                    $query->inFolder($request->folder_id);
                }
            }

            // Filter by MIME type
            if ($request->has('mime_type')) {
                $query->byMimeType($request->mime_type);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('filename', 'like', "%{$search}%")
                        ->orWhere('original_filename', 'like', "%{$search}%");
                });
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $documents = $query->paginate($request->get('per_page', 15));

            return response()->json($documents);
        }

        // For web requests, return view with Livewire component
        return view('afterburner-documents::documents.index', [
            'team' => $team,
        ]);
    }

    /**
     * Store a newly created document.
     */
    public function store(Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'folder_id' => 'nullable|exists:folders,id',
            'name' => 'required|string|max:255',
            'file' => 'nullable|file|max:'.(config('afterburner-documents.upload.max_file_size', 2147483648) / 1024),
            'storage_path' => 'nullable|string', // For chunked uploads
            'retention_tag_id' => 'nullable|exists:retention_tags,id',
        ]);

        $team = $request->user()->currentTeam;
        
        if ($team->id != $request->team_id) {
            abort(403, 'You can only create documents for your current team.');
        }

        $data = $request->only([
            'team_id',
            'folder_id',
            'name',
            'storage_path',
            'retention_tag_id',
        ]);

        // Handle file upload
        $file = $request->file('file');
        
        if ($file) {
            $data['filename'] = $file->getClientOriginalName();
            $data['original_filename'] = $file->getClientOriginalName();
            $data['mime_type'] = $file->getMimeType();
            $data['file_size'] = $file->getSize();
        } elseif ($request->has('storage_path')) {
            // For chunked uploads, get metadata from request
            $data['filename'] = $request->input('filename', basename($request->storage_path));
            $data['original_filename'] = $request->input('original_filename', $data['filename']);
            $data['mime_type'] = $request->input('mime_type', 'application/octet-stream');
            $data['file_size'] = $request->input('file_size', 0);
        }

        $document = $this->createDocument->execute($data, $file, $request->user());

        return response()->json($document->load(['folder', 'creator', 'retentionTag', 'permissions']), 201);
    }

    /**
     * Display the specified document.
     */
    public function show(Request $request, Document $document)
    {
        Gate::authorize('view', $document);

        $document->load(['folder', 'creator', 'updater', 'retentionTag', 'permissions', 'versions.creator']);

        // If API request, return JSON
        if ($request->wantsJson() || $request->expectsJson()) {
        return response()->json($document);
        }

        // For web requests, return view with Livewire component
        return view('afterburner-documents::documents.show-wrapper', [
            'document' => $document,
        ]);
    }

    /**
     * Update the specified document.
     */
    public function update(Request $request, Document $document)
    {
        Gate::authorize('update', $document);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'folder_id' => 'nullable|exists:folders,id',
            'file' => 'nullable|file|max:'.(config('afterburner-documents.upload.max_file_size', 2147483648) / 1024),
            'storage_path' => 'nullable|string', // For chunked uploads
            'retention_tag_id' => 'nullable|exists:retention_tags,id',
            'change_summary' => 'nullable|string|max:500',
        ]);

        $data = $request->only([
            'name',
            'folder_id',
            'storage_path',
            'retention_tag_id',
            'change_summary',
        ]);

        // Handle file update
        $file = $request->file('file');
        
        if ($file) {
            $data['filename'] = $file->getClientOriginalName();
            $data['mime_type'] = $file->getMimeType();
            $data['file_size'] = $file->getSize();
        } elseif ($request->has('storage_path')) {
            $data['filename'] = $request->input('filename', $document->filename);
            $data['mime_type'] = $request->input('mime_type', $document->mime_type);
            $data['file_size'] = $request->input('file_size', $document->file_size);
        }

        $document = $this->updateDocument->execute($document, $data, $file, $request->user());

        return response()->json($document->load(['folder', 'creator', 'updater', 'retentionTag', 'permissions']));
    }

    /**
     * Remove the specified document.
     */
    public function destroy(Request $request, Document $document)
    {
        Gate::authorize('delete', $document);

        $force = $request->boolean('force', false);
        $deleteFiles = $request->boolean('delete_files', false);

        $this->deleteDocument->execute($document, $request->user(), $force, $deleteFiles);

        return response()->json(['message' => 'Document deleted successfully'], 200);
    }

    /**
     * Download the specified document.
     */
    public function download(Request $request, Document $document)
    {
        Gate::authorize('download', $document);

        $disk = $document->storage_disk ?? 'r2';
        
        if (!Storage::disk($disk)->exists($document->storage_path)) {
            abort(404, 'File not found');
        }

        // Log download
        \Afterburner\Documents\Models\DocumentAudit::logAction(
            $document,
            $request->user(),
            'downloaded'
        );

        return Storage::disk($disk)->download(
            $document->storage_path,
            $document->original_filename
        );
    }

    /**
     * Get a preview URL for the document.
     */
    public function preview(Request $request, Document $document)
    {
        Gate::authorize('view', $document);

        $expirationMinutes = $request->get('expires', 60);
        $url = $this->storageService->getTemporaryUrl($document, $expirationMinutes);

        if (!$url) {
            abort(404, 'Preview not available');
        }

        // Log view
        \Afterburner\Documents\Models\DocumentAudit::logAction(
            $document,
            $request->user(),
            'viewed'
        );

        return response()->json([
            'url' => $url,
            'expires_at' => now()->addMinutes($expirationMinutes),
        ]);
    }
}

