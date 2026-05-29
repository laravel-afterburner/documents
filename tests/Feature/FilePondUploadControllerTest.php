<?php

namespace Afterburner\Documents\Tests\Feature;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\UploadSession;
use Afterburner\Documents\Services\StorageService;
use Afterburner\Documents\Tests\TestCase;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

class FilePondUploadControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            EnsureEmailIsVerified::class,
        ]);
        Notification::fake();
    }

    public function test_small_file_can_be_uploaded_via_process_endpoint(): void
    {
        [$user, $team] = $this->createTeamWithUser();

        $file = UploadedFile::fake()->create('notes.txt', 2, 'text/plain');
        file_put_contents($file->getRealPath(), 'hi');

        $response = $this->actingAs($user)->post(
            route('teams.documents.upload.process', $team),
            [
                'file' => $file,
            ]
        );

        $response->assertOk();

        $document = Document::query()->first();

        $this->assertNotNull($document);
        $this->assertSame('completed', $document->upload_status);
        $this->assertSame('notes.txt', $document->filename);
        $this->assertTrue(app(StorageService::class)->exists($document->storage_path));
    }

    public function test_chunked_upload_can_be_initialized_patched_and_completed(): void
    {
        [$user, $team] = $this->createTeamWithUser();

        $payload = str_repeat('a', 2048);

        $initResponse = $this->actingAs($user)->post(
            route('teams.documents.upload.process', $team),
            [],
            [
                'Upload-Length' => (string) strlen($payload),
                'Upload-Name' => 'large.txt',
                'Content-Type' => 'text/plain',
            ]
        );

        $initResponse->assertOk();
        $uploadId = trim($initResponse->getContent());
        $this->assertTrue(Str::isUuid($uploadId));

        $this->assertDatabaseHas('upload_sessions', [
            'id' => $uploadId,
            'total_size' => strlen($payload),
            'status' => 'uploading',
        ]);

        $firstChunk = substr($payload, 0, 1024);
        $secondChunk = substr($payload, 1024);

        $this->actingAs($user);

        $this->patchUpload(
            route('teams.documents.upload.patch', [$team, $uploadId]),
            $firstChunk,
            [
                'Upload-Offset' => '0',
                'Upload-Length' => (string) strlen($payload),
                'Upload-Name' => 'large.txt',
            ]
        )->assertNoContent();

        $headResponse = $this->actingAs($user)->head(
            route('teams.documents.upload.head', [$team, $uploadId])
        );

        $headResponse->assertOk();
        $this->assertSame('1024', $headResponse->headers->get('Upload-Offset'));

        $this->patchUpload(
            route('teams.documents.upload.patch', [$team, $uploadId]),
            $secondChunk,
            [
                'Upload-Offset' => '1024',
                'Upload-Length' => (string) strlen($payload),
                'Upload-Name' => 'large.txt',
            ]
        )->assertNoContent();

        $session = UploadSession::query()->findOrFail($uploadId);
        $document = Document::query()->findOrFail($session->document_id);

        $this->assertSame('completed', $session->fresh()->status);
        $this->assertSame('completed', $document->upload_status);
        $this->assertSame(strlen($payload), $document->size);
        $this->assertSame($payload, Storage::disk('r2')->get($document->storage_path));
    }

    public function test_chunked_upload_rejects_unexpected_offset(): void
    {
        [$user, $team] = $this->createTeamWithUser();

        $initResponse = $this->actingAs($user)->post(
            route('teams.documents.upload.process', $team),
            [],
            [
                'Upload-Length' => '10',
                'Upload-Name' => 'large.txt',
                'Content-Type' => 'text/plain',
            ]
        );

        $initResponse->assertOk();
        $uploadId = trim($initResponse->getContent());
        $this->assertTrue(Str::isUuid($uploadId));

        $this->actingAs($user);

        $this->patchUpload(
            route('teams.documents.upload.patch', [$team, $uploadId]),
            '1234567890',
            [
                'Upload-Offset' => '5',
                'Upload-Length' => '10',
                'Upload-Name' => 'large.txt',
            ]
        )->assertStatus(409);
    }

    public function test_upload_session_can_be_reverted(): void
    {
        [$user, $team] = $this->createTeamWithUser();

        $initResponse = $this->actingAs($user)->post(
            route('teams.documents.upload.process', $team),
            [],
            [
                'Upload-Length' => '10',
                'Upload-Name' => 'large.txt',
                'Content-Type' => 'text/plain',
            ]
        );

        $initResponse->assertOk();
        $uploadId = trim($initResponse->getContent());
        $this->assertTrue(Str::isUuid($uploadId));

        $this->actingAs($user)->delete(
            route('teams.documents.upload.revert', [$team, $uploadId])
        )->assertNoContent();

        $this->assertDatabaseMissing('upload_sessions', ['id' => $uploadId]);
        $this->assertSame(0, Document::query()->count());
    }

    protected function patchUpload(string $url, string $content, array $headers): TestResponse
    {
        return $this->call(
            'PATCH',
            $url,
            [],
            [],
            [],
            $this->transformHeadersToServerVars(array_merge([
                'CONTENT_TYPE' => 'application/offset+octet-stream',
            ], $headers)),
            $content
        );
    }

    public function test_init_rejects_files_exceeding_max_size(): void
    {
        [$user, $team] = $this->createTeamWithUser();

        config(['afterburner-documents.upload.max_file_size' => 100]);

        $this->actingAs($user)->post(
            route('teams.documents.upload.process', $team),
            [],
            [
                'Upload-Length' => '500',
                'Upload-Name' => 'large.txt',
                'Content-Type' => 'text/plain',
            ]
        )->assertStatus(422);
    }

    public function test_chunked_init_accepts_multipart_form_data_with_folder_id(): void
    {
        [$user, $team, $folder] = $this->createTeamWithUserAndFolder();

        $this->actingAs($user)->post(
            route('teams.documents.upload.process', $team),
            [
                'folderId' => (string) $folder->id,
                'notes' => 'Quarterly report',
            ],
            [
                'Upload-Length' => '2048',
                'Upload-Name' => 'report.pdf',
                'Content-Type' => 'multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW',
            ]
        )->assertOk();
    }

    public function test_chunked_init_accepts_upload_length_without_upload_name_header(): void
    {
        [$user, $team] = $this->createTeamWithUser();

        $this->actingAs($user)->post(
            route('teams.documents.upload.process', $team),
            [
                'filepond' => json_encode(['name' => 'notes.txt']),
            ],
            [
                'Upload-Length' => '2048',
                'Content-Type' => 'multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW',
            ]
        )->assertOk();
    }

    public function test_init_rejects_disallowed_file_types_inferred_from_filename(): void
    {
        [$user, $team] = $this->createTeamWithUser();

        config([
            'afterburner-documents.upload.allowed_mime_types' => ['application/pdf'],
        ]);

        $this->actingAs($user)->post(
            route('teams.documents.upload.process', $team),
            [],
            [
                'Upload-Length' => '2048',
                'Upload-Name' => 'video.mp4',
                'Content-Type' => 'multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW',
            ]
        )->assertStatus(422);
    }

    public function test_init_rejects_duplicate_document_names_in_the_same_folder(): void
    {
        [$user, $team, $folder] = $this->createTeamWithUserAndFolder();

        Document::query()->create([
            'team_id' => $team->id,
            'folder_id' => $folder->id,
            'name' => 'Quarterly report',
            'filename' => 'Quarterly report.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'storage_path' => 'documents/test.pdf',
            'upload_status' => 'completed',
            'upload_progress' => 100,
            'uploaded_by' => $user->id,
        ]);

        $this->actingAs($user)->post(
            route('teams.documents.upload.process', $team),
            [
                'folderId' => (string) $folder->id,
            ],
            [
                'Upload-Length' => '2048',
                'Upload-Name' => 'Quarterly report.pdf',
                'Content-Type' => 'text/plain',
                'Accept' => 'text/plain',
            ]
        )
            ->assertStatus(422)
            ->assertSee('Document with name \'Quarterly report\' already exists in this folder.', false);

        $this->assertSame(1, Document::query()->count());
        $this->assertDatabaseCount('upload_sessions', 0);
    }
}
