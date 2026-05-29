<?php

namespace Afterburner\Documents\Jobs;

use Afterburner\Documents\Actions\FinalizeDocumentUpload;
use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\UploadSession;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class FinalizeDocumentUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 3600;

    public function __construct(
        public int $documentId,
        public string $sessionPartPath,
        public int $userId,
        public ?string $uploadSessionId = null,
    ) {}

    public function handle(FinalizeDocumentUpload $finalizeDocumentUpload): void
    {
        $document = Document::query()->findOrFail($this->documentId);
        $user = User::query()->findOrFail($this->userId);

        $finalizeDocumentUpload->executeFromSessionPart(
            $document,
            $this->sessionPartPath,
            $user
        );

        if ($this->uploadSessionId) {
            UploadSession::query()
                ->whereKey($this->uploadSessionId)
                ->update([
                    'status' => 'completed',
                    'expires_at' => null,
                ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Document::query()
            ->whereKey($this->documentId)
            ->update([
                'upload_status' => 'failed',
                'upload_progress' => 0,
            ]);

        if ($this->uploadSessionId) {
            UploadSession::query()
                ->whereKey($this->uploadSessionId)
                ->update(['status' => 'failed']);
        }
    }
}
