<?php

namespace Afterburner\Documents\Http\Controllers;

use Afterburner\Documents\Actions\UploadDocument;
use Afterburner\Documents\Exceptions\DuplicateDocumentException;
use Afterburner\Documents\Jobs\FinalizeDocumentUploadJob;
use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\Folder;
use Afterburner\Documents\Models\UploadSession;
use Afterburner\Documents\Services\StorageService;
use Afterburner\Documents\Support\DocumentUploadRules;
use Afterburner\Documents\Support\SubscriptionEntitlementGate;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class FilePondUploadController
{
    public function __construct(
        protected StorageService $storageService,
        protected UploadDocument $uploadDocument,
    ) {}

    /**
     * FilePond process endpoint — small file upload or chunked init.
     */
    public function process(Request $request, Team $team): Response
    {
        if ($this->isChunkedInitRequest($request)) {
            return $this->init($request, $team);
        }

        return $this->processSmallFile($request, $team);
    }

    /**
     * Initialize a FilePond chunked upload (POST without file body).
     */
    protected function init(Request $request, Team $team): Response
    {
        $this->authorizeTeam($team);

        $totalSize = (int) $request->header('Upload-Length', 0);
        $filename = $this->resolveChunkedUploadFilename($request);
        $mimeType = DocumentUploadRules::resolveMimeType(
            $filename,
            $request->header('Content-Type')
        );

        if ($totalSize <= 0 || $filename === '') {
            return $this->uploadErrorResponse('Upload-Length header and a filename are required.');
        }

        if ($totalSize > DocumentUploadRules::maxFileSizeBytes()) {
            return $this->uploadErrorResponse('File exceeds the maximum allowed size.');
        }

        if (DocumentUploadRules::exceedsMaxChunks($totalSize)) {
            return $this->uploadErrorResponse('File requires too many upload chunks.');
        }

        if ($mimeType !== null && ! DocumentUploadRules::mimeTypeAllowed($mimeType)) {
            return $this->uploadErrorResponse('File type is not allowed.');
        }

        $folderId = $request->input('folderId');
        $notes = $request->input('notes');

        if ($folderId && ! $this->folderBelongsToTeam((int) $folderId, $team)) {
            return $this->uploadErrorResponse('The selected folder is invalid.');
        }

        if ($response = $this->ensureStorageAllowsUpload($team, $totalSize)) {
            return $response;
        }

        $uploadId = (string) Str::uuid();

        try {
            DB::transaction(function () use ($team, $folderId, $filename, $mimeType, $totalSize, $notes, $uploadId) {
                $document = $this->uploadDocument->execute(
                    $team->id,
                    $folderId ? (int) $folderId : null,
                    $filename,
                    $mimeType ?? 'application/octet-stream',
                    $totalSize,
                    Auth::user(),
                    false,
                    is_string($notes) && $notes !== '' ? $notes : null
                );

                $partPath = $this->storageService->createUploadSessionPart($uploadId);

                $document->updateQuietly([
                    'upload_status' => 'uploading',
                    'upload_progress' => 0,
                ]);

                UploadSession::create([
                    'id' => $uploadId,
                    'team_id' => $team->id,
                    'user_id' => Auth::id(),
                    'folder_id' => $folderId ? (int) $folderId : null,
                    'document_id' => $document->id,
                    'filename' => $filename,
                    'mime_type' => $mimeType,
                    'total_size' => $totalSize,
                    'bytes_received' => 0,
                    'storage_path' => $partPath,
                    'notes' => is_string($notes) && $notes !== '' ? $notes : null,
                    'status' => 'uploading',
                    'expires_at' => now()->addHours(DocumentUploadRules::sessionTtlHours()),
                ]);
            });
        } catch (DuplicateDocumentException $exception) {
            return $this->uploadErrorResponse($exception->getMessage());
        }

        return response($uploadId, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Store a small file uploaded in a single request via FilePond process.
     */
    protected function processSmallFile(Request $request, Team $team): Response
    {
        $this->authorizeTeam($team);

        $file = $this->resolveUploadedFile($request);

        if (! $file) {
            return $this->uploadErrorResponse('No file was uploaded.');
        }

        if ($file->getSize() > DocumentUploadRules::maxFileSizeBytes()) {
            return $this->uploadErrorResponse('File exceeds the maximum allowed size.');
        }

        if (! DocumentUploadRules::mimeTypeAllowed($file->getMimeType())) {
            return $this->uploadErrorResponse('File type is not allowed.');
        }

        $folderId = $request->input('folderId');
        $notes = $request->input('notes');

        if ($folderId && ! $this->folderBelongsToTeam((int) $folderId, $team)) {
            return $this->uploadErrorResponse('The selected folder is invalid.');
        }

        if ($response = $this->ensureStorageAllowsUpload($team, $file->getSize())) {
            return $response;
        }

        try {
            $document = DB::transaction(function () use ($team, $folderId, $file, $notes) {
                $document = $this->uploadDocument->execute(
                    $team->id,
                    $folderId ? (int) $folderId : null,
                    $file->getClientOriginalName(),
                    $file->getMimeType() ?: 'application/octet-stream',
                    $file->getSize(),
                    Auth::user(),
                    false,
                    is_string($notes) && $notes !== '' ? $notes : null
                );

                $partPath = $this->storageService->stageFileAtUploadSessionPart($file->getRealPath());

                $document->updateQuietly([
                    'upload_status' => 'processing',
                    'upload_progress' => 100,
                ]);

                $this->dispatchCloudFinalizeJob($document->id, $partPath);

                return $document;
            });
        } catch (DuplicateDocumentException $exception) {
            return $this->uploadErrorResponse($exception->getMessage());
        }

        return response((string) $document->id, 200)->header('Content-Type', 'text/plain');
    }

    protected function isChunkedInitRequest(Request $request): bool
    {
        return ! $this->resolveUploadedFile($request)
            && $request->headers->has('Upload-Length');
    }

    protected function resolveChunkedUploadFilename(Request $request): string
    {
        $filename = (string) $request->header('Upload-Name', '');

        if ($filename !== '') {
            return $filename;
        }

        foreach ($request->except(['folderId', 'notes']) as $value) {
            if (! is_string($value)) {
                continue;
            }

            $decoded = json_decode($value, true);

            if (! is_array($decoded)) {
                continue;
            }

            foreach (['name', 'filename', 'fileName'] as $key) {
                if (! empty($decoded[$key]) && is_string($decoded[$key])) {
                    return $decoded[$key];
                }
            }
        }

        return '';
    }

    protected function resolveUploadedFile(Request $request): ?UploadedFile
    {
        if ($request->hasFile('file')) {
            return $request->file('file');
        }

        foreach (array_keys($request->allFiles()) as $key) {
            $candidate = $request->file($key);

            if ($candidate instanceof UploadedFile) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Receive a chunk (PATCH with Upload-Offset header).
     */
    public function patch(Request $request, Team $team, string $uploadId): Response
    {
        $this->authorizeTeam($team);

        $session = $this->findAuthorizedSession($team, $uploadId);

        if ($session->status !== 'uploading') {
            abort(409, 'Upload session is not accepting chunks.');
        }

        $offset = (int) $request->header('Upload-Offset', -1);
        $chunkData = $request->getContent();

        if ($offset < 0 || $chunkData === '') {
            return $this->uploadErrorResponse('Upload-Offset header and chunk body are required.');
        }

        if ($offset !== (int) $session->bytes_received) {
            abort(409, 'Unexpected upload offset.');
        }

        $newBytesReceived = $this->storageService->appendToUploadSessionPart(
            $session->storage_path,
            $offset,
            $chunkData
        );

        $session->update(['bytes_received' => $newBytesReceived]);

        if ($session->document) {
            $session->document->updateQuietly([
                'upload_status' => 'uploading',
                'upload_progress' => $session->fresh()->uploadProgress(),
            ]);
        }

        if ($newBytesReceived >= $session->total_size) {
            $this->completeSession($session);
        }

        return response('', 204);
    }

    /**
     * Resume an upload (HEAD returns Upload-Offset).
     */
    public function head(Request $request, Team $team, string $uploadId): Response
    {
        $this->authorizeTeam($team);

        $session = $this->findAuthorizedSession($team, $uploadId);

        return response('', 200)->header('Upload-Offset', (string) $session->bytes_received);
    }

    /**
     * Cancel and clean up an upload session (DELETE / revert).
     */
    public function revert(Request $request, Team $team, string $uploadId): Response
    {
        $this->authorizeTeam($team);

        $session = $this->findAuthorizedSession($team, $uploadId);

        $this->abortSession($session);

        return response('', 204);
    }

    protected function completeSession(UploadSession $session): void
    {
        if (! $session->document) {
            abort(500, 'Upload session is missing a document record.');
        }

        $session->document->updateQuietly([
            'upload_status' => 'processing',
            'upload_progress' => 100,
        ]);

        $session->update([
            'status' => 'processing',
            'expires_at' => now()->addHours(DocumentUploadRules::sessionTtlHours()),
        ]);

        $this->dispatchCloudFinalizeJob(
            $session->document_id,
            $session->storage_path,
            $session->id
        );
    }

    protected function dispatchCloudFinalizeJob(int $documentId, string $sessionPartPath, ?string $uploadSessionId = null): void
    {
        FinalizeDocumentUploadJob::dispatch(
            $documentId,
            $sessionPartPath,
            Auth::id(),
            $uploadSessionId,
        );
    }

    protected function abortSession(UploadSession $session): void
    {
        DB::transaction(function () use ($session) {
            $this->storageService->deleteUploadSessionPart($session->storage_path);

            if ($session->document) {
                $session->document->updateQuietly([
                    'upload_status' => 'failed',
                    'upload_progress' => 0,
                ]);

                $session->document->forceDelete();
            }

            $session->update([
                'status' => 'abandoned',
                'expires_at' => now(),
            ]);

            $session->delete();
        });
    }

    protected function authorizeTeam(Team $team): void
    {
        Gate::authorize('create', [Document::class, $team]);
    }

    protected function ensureStorageAllowsUpload(Team $team, int $bytes): ?Response
    {
        if (! SubscriptionEntitlementGate::allowsStorageForUpload($team, $bytes)) {
            return $this->uploadErrorResponse('Storage limit exceeded for your subscription plan.', 403);
        }

        return null;
    }

    protected function findAuthorizedSession(Team $team, string $uploadId): UploadSession
    {
        $session = UploadSession::query()
            ->where('id', $uploadId)
            ->where('team_id', $team->id)
            ->where('user_id', Auth::id())
            ->first();

        if (! $session) {
            abort(404, 'Upload session not found.');
        }

        return $session;
    }

    protected function folderBelongsToTeam(int $folderId, Team $team): bool
    {
        return Folder::query()
            ->where('id', $folderId)
            ->where('team_id', $team->id)
            ->exists();
    }

    protected function uploadErrorResponse(string $message, int $status = 422): Response
    {
        return response($message, $status)->header('Content-Type', 'text/plain');
    }
}
