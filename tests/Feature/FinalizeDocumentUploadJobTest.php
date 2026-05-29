<?php

namespace Afterburner\Documents\Tests\Feature;

use Afterburner\Documents\Jobs\FinalizeDocumentUploadJob;
use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\UploadSession;
use Afterburner\Documents\Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

class FinalizeDocumentUploadJobTest extends TestCase
{
    public function test_last_chunk_patch_dispatches_cloud_finalize_job(): void
    {
        Queue::fake();

        [$user, $team] = $this->createTeamWithUser();
        $payload = str_repeat('a', 2048);

        $this->actingAs($user);

        $uploadId = trim($this->post(
            route('teams.documents.upload.process', $team),
            [],
            [
                'Upload-Length' => (string) strlen($payload),
                'Upload-Name' => 'large.txt',
                'Content-Type' => 'text/plain',
            ]
        )->getContent());

        $this->patchUpload(
            route('teams.documents.upload.patch', [$team, $uploadId]),
            substr($payload, 0, 1024),
            [
                'Upload-Offset' => '0',
                'Upload-Length' => (string) strlen($payload),
                'Upload-Name' => 'large.txt',
            ]
        )->assertNoContent();

        $this->patchUpload(
            route('teams.documents.upload.patch', [$team, $uploadId]),
            substr($payload, 1024),
            [
                'Upload-Offset' => '1024',
                'Upload-Length' => (string) strlen($payload),
                'Upload-Name' => 'large.txt',
            ]
        )->assertNoContent();

        $session = UploadSession::query()->findOrFail($uploadId);
        $document = Document::query()->findOrFail($session->document_id);

        $this->assertSame('processing', $session->fresh()->status);
        $this->assertSame('processing', $document->fresh()->upload_status);
        $this->assertSame(100, $document->fresh()->upload_progress);

        Queue::assertPushed(FinalizeDocumentUploadJob::class, function (FinalizeDocumentUploadJob $job) use ($document, $session) {
            return $job->documentId === $document->id
                && $job->sessionPartPath === $session->storage_path
                && $job->uploadSessionId === $session->id;
        });
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
}
