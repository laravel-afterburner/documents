<?php

namespace Afterburner\Documents\Support;

use Afterburner\Documents\Models\Document;
use Illuminate\Support\Carbon;

class DocumentUploadNotificationRules
{
    public static function shouldNotifyOnComplete(Document $document): bool
    {
        if (! config('afterburner-documents.upload.notify_on_complete.enabled', true)) {
            return false;
        }

        $minBytes = (int) config('afterburner-documents.upload.notify_on_complete.min_bytes', 10485760);

        if ($minBytes > 0 && (int) $document->size < $minBytes) {
            return false;
        }

        $minSeconds = (int) config('afterburner-documents.upload.notify_on_complete.min_seconds', 30);
        $startedAt = $document->created_at;

        if (! $startedAt instanceof Carbon) {
            return false;
        }

        return $startedAt->diffInSeconds(now()) >= $minSeconds;
    }
}
