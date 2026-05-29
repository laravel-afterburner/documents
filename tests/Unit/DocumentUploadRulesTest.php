<?php

namespace Afterburner\Documents\Tests\Unit;

use Afterburner\Documents\Support\DocumentUploadRules;
use Afterburner\Documents\Tests\TestCase;

class DocumentUploadRulesTest extends TestCase
{
    public function test_max_file_size_kilobytes_converts_from_bytes(): void
    {
        config(['afterburner-documents.upload.max_file_size' => 2048]);

        $this->assertSame(2, DocumentUploadRules::maxFileSizeKilobytes());
    }

    public function test_exceeds_max_chunks_when_file_is_too_large(): void
    {
        config([
            'afterburner-documents.upload.chunk_size' => 100,
            'afterburner-documents.upload.max_chunks' => 5,
        ]);

        $this->assertTrue(DocumentUploadRules::exceedsMaxChunks(1000));
        $this->assertFalse(DocumentUploadRules::exceedsMaxChunks(400));
    }

    public function test_mime_type_allowed_respects_configuration(): void
    {
        $this->assertTrue(DocumentUploadRules::mimeTypeAllowed('text/plain'));
        $this->assertFalse(DocumentUploadRules::mimeTypeAllowed('application/x-msdownload'));
    }
}
