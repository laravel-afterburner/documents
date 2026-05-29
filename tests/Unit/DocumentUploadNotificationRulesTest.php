<?php

namespace Afterburner\Documents\Tests\Unit;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Support\DocumentUploadNotificationRules;
use Afterburner\Documents\Tests\TestCase;
use Illuminate\Support\Carbon;

class DocumentUploadNotificationRulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'afterburner-documents.upload.notify_on_complete.enabled' => true,
            'afterburner-documents.upload.notify_on_complete.min_seconds' => 30,
            'afterburner-documents.upload.notify_on_complete.min_bytes' => 10485760,
        ]);
    }

    public function test_does_not_notify_when_disabled(): void
    {
        config(['afterburner-documents.upload.notify_on_complete.enabled' => false]);

        $document = $this->makeDocument(size: 20971520, createdAt: now()->subMinutes(2));

        $this->assertFalse(DocumentUploadNotificationRules::shouldNotifyOnComplete($document));
    }

    public function test_does_not_notify_when_upload_is_too_quick(): void
    {
        $document = $this->makeDocument(size: 20971520, createdAt: now()->subSeconds(10));

        $this->assertFalse(DocumentUploadNotificationRules::shouldNotifyOnComplete($document));
    }

    public function test_does_not_notify_when_file_is_below_size_floor(): void
    {
        $document = $this->makeDocument(size: 1048576, createdAt: now()->subMinutes(2));

        $this->assertFalse(DocumentUploadNotificationRules::shouldNotifyOnComplete($document));
    }

    public function test_notifies_when_upload_exceeds_time_and_size_thresholds(): void
    {
        $document = $this->makeDocument(size: 20971520, createdAt: now()->subMinutes(2));

        $this->assertTrue(DocumentUploadNotificationRules::shouldNotifyOnComplete($document));
    }

    public function test_notifies_on_time_threshold_when_size_floor_is_disabled(): void
    {
        config(['afterburner-documents.upload.notify_on_complete.min_bytes' => 0]);

        $document = $this->makeDocument(size: 1024, createdAt: now()->subMinutes(2));

        $this->assertTrue(DocumentUploadNotificationRules::shouldNotifyOnComplete($document));
    }

    protected function makeDocument(int $size, Carbon $createdAt): Document
    {
        [$user, $team] = $this->createTeamWithUser();

        $document = Document::query()->create([
            'team_id' => $team->id,
            'folder_id' => null,
            'name' => 'sample',
            'filename' => 'sample.txt',
            'mime_type' => 'text/plain',
            'size' => $size,
            'storage_path' => '',
            'upload_status' => 'processing',
            'upload_progress' => 100,
            'uploaded_by' => $user->id,
        ]);

        $document->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $document->fresh();
    }
}
