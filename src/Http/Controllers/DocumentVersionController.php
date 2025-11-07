<?php

namespace Afterburner\Documents\Http\Controllers;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\DocumentVersion;
use Afterburner\Documents\Services\DocumentStorageService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controller;

class DocumentVersionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected DocumentStorageService $storageService
    ) {
    }

    /**
     * Display a listing of document versions.
     */
    public function index(Request $request, Document $document)
    {
        Gate::authorize('view', $document);

        $versions = $document->versions()
            ->with('creator')
            ->orderBy('version_number', 'desc')
            ->get();

        return response()->json($versions);
    }

    /**
     * Display the specified version.
     */
    public function show(Request $request, Document $document, DocumentVersion $version)
    {
        Gate::authorize('view', $document);

        // Verify version belongs to document
        if ($version->document_id !== $document->id) {
            abort(404);
        }

        $version->load('creator');

        return response()->json($version);
    }

    /**
     * Restore a document to a specific version.
     */
    public function restore(Request $request, Document $document, DocumentVersion $version)
    {
        Gate::authorize('update', $document);

        // Verify version belongs to document
        if ($version->document_id !== $document->id) {
            abort(404);
        }

        // Create new version from current state before restoring
        if (config('afterburner-documents.versioning.enabled', true) &&
            config('afterburner-documents.versioning.auto_version_on_update', true)) {
            $document->createVersion(
                $document->storage_path,
                $document->file_size,
                $document->mime_type,
                $request->user()->id,
                'Backup before restoring to version '.$version->version_number
            );
        }

        // Restore version
        $document->update([
            'storage_path' => $version->storage_path,
            'filename' => $version->filename,
            'file_size' => $version->file_size,
            'mime_type' => $version->mime_type,
            'version' => $document->version + 1,
            'updated_by' => $request->user()->id,
        ]);

        // Create new version record for restored version
        DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => $document->version,
            'filename' => $version->filename,
            'storage_path' => $version->storage_path,
            'file_size' => $version->file_size,
            'mime_type' => $version->mime_type,
            'created_by' => $request->user()->id,
            'change_summary' => 'Restored from version '.$version->version_number,
        ]);

        // Log audit trail
        \Afterburner\Documents\Models\DocumentAudit::logAction(
            $document,
            $request->user(),
            'updated',
            [
                'action' => 'restored_version',
                'restored_version' => $version->version_number,
            ]
        );

        return response()->json($document->fresh()->load(['versions', 'updater']));
    }

    /**
     * Download a specific version.
     */
    public function download(Request $request, Document $document, DocumentVersion $version)
    {
        Gate::authorize('view', $document);

        // Verify version belongs to document
        if ($version->document_id !== $document->id) {
            abort(404);
        }

        $disk = $document->storage_disk ?? 'r2';
        
        if (!Storage::disk($disk)->exists($version->storage_path)) {
            abort(404, 'Version file not found');
        }

        // Log download
        \Afterburner\Documents\Models\DocumentAudit::logAction(
            $document,
            $request->user(),
            'downloaded',
            [
                'version' => $version->version_number,
            ]
        );

        return Storage::disk($disk)->download(
            $version->storage_path,
            $version->filename
        );
    }
}

