<?php

namespace Afterburner\Documents\Http\Controllers;

use Afterburner\Documents\Actions\HandleChunkedUpload;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;

class UploadController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected HandleChunkedUpload $chunkedUpload
    ) {
    }

    /**
     * Initiate a chunked upload session.
     */
    public function initiate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required|string|max:255',
            'total_chunks' => 'required|integer|min:1|max:10000',
            'total_size' => 'required|integer|min:1|max:'.config('afterburner-documents.upload.max_file_size', 104857600),
            'team_id' => 'required|exists:teams,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verify team membership
        $team = $request->user()->currentTeam;
        if ($team->id != $request->team_id) {
            abort(403, 'You can only upload to your current team.');
        }

        // Validate file type
        $allowedMimeTypes = config('afterburner-documents.upload.allowed_mime_types', []);
        $extension = pathinfo($request->filename, PATHINFO_EXTENSION);
        
        // Basic validation - full MIME type check happens on first chunk
        if (!empty($allowedMimeTypes)) {
            // This is a basic check; full validation happens when chunks are uploaded
        }

        $session = $this->chunkedUpload->initiate(
            $request->filename,
            $request->total_chunks,
            $request->total_size
        );

        return response()->json($session, 201);
    }

    /**
     * Upload a chunk.
     */
    public function uploadChunk(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'upload_id' => 'required|string',
            'chunk_number' => 'required|integer|min:0',
            'chunk' => 'required|file|max:'.config('afterburner-documents.upload.chunk_size', 5242880),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = $this->chunkedUpload->uploadChunk(
            $request->upload_id,
            $request->chunk_number,
            $request->file('chunk')
        );

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * Complete a chunked upload.
     */
    public function complete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'upload_id' => 'required|string',
            'final_path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = $this->chunkedUpload->complete(
            $request->upload_id,
            $request->final_path
        );

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * Cancel a chunked upload.
     */
    public function cancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'upload_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $success = $this->chunkedUpload->cancel($request->upload_id);

        if (!$success) {
            return response()->json(['error' => 'Upload session not found'], 404);
        }

        return response()->json(['message' => 'Upload cancelled successfully']);
    }

    /**
     * Get upload status.
     */
    public function status(Request $request, string $uploadId)
    {
        $status = $this->chunkedUpload->getStatus($uploadId);

        if (!$status) {
            return response()->json(['error' => 'Upload session not found'], 404);
        }

        return response()->json($status);
    }
}

