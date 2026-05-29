<?php

namespace Afterburner\Documents\Console\Commands;

use Afterburner\Documents\Models\UploadSession;
use Afterburner\Documents\Services\StorageService;
use Illuminate\Console\Command;

class CleanupUploadSessionsCommand extends Command
{
    protected $signature = 'documents:cleanup-upload-sessions';

    protected $description = 'Delete expired and abandoned document upload sessions';

    public function handle(StorageService $storageService): int
    {
        $sessions = UploadSession::query()->abandonable()->get();

        $count = 0;

        foreach ($sessions as $session) {
            $storageService->deleteUploadSessionPart($session->storage_path);

            if ($session->document) {
                if (in_array($session->document->upload_status, ['pending', 'uploading', 'failed'], true)) {
                    $session->document->forceDelete();
                } elseif ($session->document->upload_status === 'processing') {
                    $session->document->update([
                        'upload_status' => 'failed',
                        'upload_progress' => 0,
                    ]);
                }
            }

            $session->delete();
            $count++;
        }

        $this->info("Cleaned up {$count} upload session(s).");

        return Command::SUCCESS;
    }
}
