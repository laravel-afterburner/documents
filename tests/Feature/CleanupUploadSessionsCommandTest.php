<?php

namespace Afterburner\Documents\Tests\Feature;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\UploadSession;
use Afterburner\Documents\Services\StorageService;
use Afterburner\Documents\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class CleanupUploadSessionsCommandTest extends TestCase
{
    public function test_command_cleans_up_expired_upload_sessions(): void
    {
        [$user, $team] = $this->createTeamWithUser();

        $document = Document::query()->create([
            'team_id' => $team->id,
            'folder_id' => null,
            'name' => 'sample',
            'filename' => 'sample.txt',
            'mime_type' => 'text/plain',
            'size' => 10,
            'storage_path' => '',
            'upload_status' => 'uploading',
            'upload_progress' => 0,
            'uploaded_by' => $user->id,
        ]);

        $partPath = app(StorageService::class)->createUploadSessionPart('expired-session');
        Storage::disk('documents-uploads')->put('sessions/expired-session.part', 'partial');

        UploadSession::query()->create([
            'id' => 'expired-session',
            'team_id' => $team->id,
            'user_id' => $user->id,
            'folder_id' => null,
            'document_id' => $document->id,
            'filename' => 'sample.txt',
            'mime_type' => 'text/plain',
            'total_size' => 10,
            'bytes_received' => 5,
            'storage_path' => $partPath,
            'status' => 'uploading',
            'expires_at' => now()->subHour(),
        ]);

        $this->artisan('documents:cleanup-upload-sessions')
            ->expectsOutput('Cleaned up 1 upload session(s).')
            ->assertSuccessful();

        $this->assertDatabaseMissing('upload_sessions', ['id' => 'expired-session']);
        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    }

    public function test_command_marks_stuck_processing_sessions_as_failed(): void
    {
        [$user, $team] = $this->createTeamWithUser();

        $document = Document::query()->create([
            'team_id' => $team->id,
            'folder_id' => null,
            'name' => 'sample',
            'filename' => 'sample.txt',
            'mime_type' => 'text/plain',
            'size' => 10,
            'storage_path' => 'documents/sample.txt',
            'upload_status' => 'processing',
            'upload_progress' => 100,
            'uploaded_by' => $user->id,
        ]);

        UploadSession::query()->create([
            'id' => 'stuck-processing',
            'team_id' => $team->id,
            'user_id' => $user->id,
            'folder_id' => null,
            'document_id' => $document->id,
            'filename' => 'sample.txt',
            'mime_type' => 'text/plain',
            'total_size' => 10,
            'bytes_received' => 10,
            'storage_path' => 'sessions/stuck-processing.part',
            'status' => 'processing',
            'expires_at' => now()->subHour(),
        ]);

        $this->artisan('documents:cleanup-upload-sessions')
            ->expectsOutput('Cleaned up 1 upload session(s).')
            ->assertSuccessful();

        $this->assertDatabaseMissing('upload_sessions', ['id' => 'stuck-processing']);
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'upload_status' => 'failed',
            'upload_progress' => 0,
        ]);
    }
}
