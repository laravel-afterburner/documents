<?php

namespace Afterburner\Documents\Tests\Unit;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Services\StorageService;
use Afterburner\Documents\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StorageServiceTest extends TestCase
{
    public function test_upload_session_part_can_be_appended_and_finalized(): void
    {
        [$user, $team] = $this->createTeamWithUser();

        $document = Document::query()->create([
            'team_id' => $team->id,
            'folder_id' => null,
            'name' => 'sample',
            'filename' => 'sample.txt',
            'mime_type' => 'text/plain',
            'size' => 11,
            'storage_path' => '',
            'upload_status' => 'uploading',
            'upload_progress' => 0,
            'uploaded_by' => $user->id,
        ]);

        $storage = app(StorageService::class);
        $partPath = $storage->createUploadSessionPart('session-123');

        $storage->appendToUploadSessionPart($partPath, 0, 'hello world');
        $destination = $storage->generateStoragePath($document);

        $this->assertTrue($storage->finalizeUploadSessionPart($partPath, $destination));
        $this->assertTrue($storage->exists($destination));
        $this->assertSame('hello world', Storage::disk('r2')->get($destination));
    }

    public function test_store_document_from_path_streams_to_disk(): void
    {
        $file = UploadedFile::fake()->create('example.txt', 4, 'text/plain');
        file_put_contents($file->getRealPath(), 'test');

        $storage = app(StorageService::class);
        $this->assertTrue($storage->storeDocumentFromPath($file->getRealPath(), 'documents/example.txt'));
        $this->assertSame('test', Storage::disk('r2')->get('documents/example.txt'));
    }

    public function test_safe_storage_filename_sanitizes_special_characters(): void
    {
        $this->assertSame(
            'ginger-snaps-coconut-crisps.pdf',
            StorageService::safeStorageFilename('Ginger Snaps & Coconut Crisps.pdf')
        );
    }

    public function test_generate_storage_path_uses_safe_filename(): void
    {
        [$user, $team] = $this->createTeamWithUser();

        $document = Document::query()->create([
            'team_id' => $team->id,
            'folder_id' => null,
            'name' => 'Cookies',
            'filename' => 'Ginger Snaps & Coconut Crisps.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'storage_path' => '',
            'upload_status' => 'completed',
            'upload_progress' => 100,
            'uploaded_by' => $user->id,
        ]);

        $path = app(StorageService::class)->generateStoragePath($document);

        $this->assertStringEndsWith('/ginger-snaps-coconut-crisps.pdf', $path);
        $this->assertStringNotContainsString('&', $path);
    }
}
