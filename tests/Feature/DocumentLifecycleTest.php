<?php

namespace Afterburner\Documents\Tests\Feature;

use Afterburner\Documents\Actions\DeleteDocument;
use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Services\StorageService;
use Afterburner\Documents\Tests\TestCase;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

class DocumentLifecycleTest extends TestCase
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

    public function test_upload_succeeds_after_soft_deleted_document_with_same_name(): void
    {
        [$user, $team, $folder] = $this->createTeamWithUserAndFolder();
        $storagePath = 'documents/1/2026/05/1/ac_sur_002.pdf';

        $existing = Document::query()->create([
            'team_id' => $team->id,
            'folder_id' => $folder->id,
            'name' => 'ac_sur_002',
            'filename' => 'ac_sur_002.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'storage_path' => $storagePath,
            'upload_status' => 'completed',
            'upload_progress' => 100,
            'uploaded_by' => $user->id,
        ]);

        $existing->createVersion($storagePath, 100, $user);
        Storage::disk('r2')->put($storagePath, 'old-content');
        $existing->delete();

        $file = UploadedFile::fake()->create('ac_sur_002.pdf', 10, 'application/pdf');
        file_put_contents($file->getRealPath(), 'new-content');

        $this->actingAs($user)->post(
            route('teams.documents.upload.process', $team),
            [
                'file' => $file,
                'folderId' => (string) $folder->id,
            ]
        )->assertOk();

        $this->assertSame(1, Document::query()->count());
        $this->assertSame(0, Document::onlyTrashed()->count());

        $document = Document::query()->firstOrFail();
        $this->assertSame('ac_sur_002', $document->name);
        $this->assertSame('completed', $document->upload_status);
        $this->assertFalse(Storage::disk('r2')->exists($storagePath));
        $this->assertTrue(Storage::disk('r2')->exists($document->storage_path));
        $this->assertSame('new-content', Storage::disk('r2')->get($document->storage_path));
    }

    public function test_delete_document_storage_removes_primary_and_version_files(): void
    {
        [$user, $team] = $this->createTeamWithUser();
        $storagePath = 'documents/1/2026/05/1/sample.pdf';
        $versionPath = 'documents/1/2026/05/1/versions/1/sample.pdf';

        $document = Document::query()->create([
            'team_id' => $team->id,
            'folder_id' => null,
            'name' => 'sample',
            'filename' => 'sample.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'storage_path' => $storagePath,
            'upload_status' => 'completed',
            'upload_progress' => 100,
            'uploaded_by' => $user->id,
        ]);

        $document->createVersion($versionPath, 100, $user);

        Storage::disk('r2')->put($storagePath, 'primary');
        Storage::disk('r2')->put($versionPath, 'version');

        app(StorageService::class)->deleteDocumentStorage($document->fresh('versions'));

        $this->assertFalse(Storage::disk('r2')->exists($storagePath));
        $this->assertFalse(Storage::disk('r2')->exists($versionPath));
    }

    public function test_soft_delete_removes_document_files_from_storage(): void
    {
        [$user, $team] = $this->createTeamWithUser();
        $storagePath = 'documents/1/2026/05/1/sample.pdf';

        $document = Document::query()->create([
            'team_id' => $team->id,
            'folder_id' => null,
            'name' => 'sample',
            'filename' => 'sample.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'storage_path' => $storagePath,
            'upload_status' => 'completed',
            'upload_progress' => 100,
            'uploaded_by' => $user->id,
        ]);

        Storage::disk('r2')->put($storagePath, 'primary');

        app(DeleteDocument::class)->execute($document, $user);

        $this->assertSoftDeleted('documents', ['id' => $document->id]);
        $this->assertFalse(Storage::disk('r2')->exists($storagePath));
    }

    public function test_chunked_upload_after_soft_delete_reuses_name(): void
    {
        [$user, $team, $folder] = $this->createTeamWithUserAndFolder();

        $existing = Document::query()->create([
            'team_id' => $team->id,
            'folder_id' => $folder->id,
            'name' => 'large-report',
            'filename' => 'large-report.txt',
            'mime_type' => 'text/plain',
            'size' => 2048,
            'storage_path' => 'documents/old.txt',
            'upload_status' => 'completed',
            'upload_progress' => 100,
            'uploaded_by' => $user->id,
        ]);
        $existing->delete();

        $payload = str_repeat('b', 2048);

        $this->actingAs($user);

        $uploadId = trim($this->post(
            route('teams.documents.upload.process', $team),
            ['folderId' => (string) $folder->id],
            [
                'Upload-Length' => (string) strlen($payload),
                'Upload-Name' => 'large-report.txt',
                'Content-Type' => 'text/plain',
            ]
        )->getContent());

        $this->patchUpload(
            route('teams.documents.upload.patch', [$team, $uploadId]),
            substr($payload, 0, 1024),
            ['Upload-Offset' => '0']
        )->assertNoContent();

        $this->patchUpload(
            route('teams.documents.upload.patch', [$team, $uploadId]),
            substr($payload, 1024),
            ['Upload-Offset' => '1024']
        )->assertNoContent();

        $document = Document::query()->firstOrFail();

        $this->assertSame('large-report', $document->name);
        $this->assertSame('completed', $document->upload_status);
        $this->assertSame($payload, Storage::disk('r2')->get($document->storage_path));
        $this->assertSame(0, Document::onlyTrashed()->count());
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
                'Upload-Length' => '2048',
                'Upload-Name' => 'large-report.txt',
            ], $headers)),
            $content
        );
    }
}
