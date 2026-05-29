<?php

namespace Afterburner\Documents\Tests\Unit;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Tests\TestCase;

class DocumentPreviewTest extends TestCase
{
    public function test_pdf_is_previewable(): void
    {
        $document = new Document([
            'mime_type' => 'application/pdf',
            'filename' => 'report.pdf',
        ]);

        $this->assertTrue($document->isPreviewableInBrowser());
        $this->assertFalse($document->isImagePreview());
    }

    public function test_png_is_image_preview(): void
    {
        $document = new Document([
            'mime_type' => 'image/png',
            'filename' => 'photo.png',
        ]);

        $this->assertTrue($document->isPreviewableInBrowser());
        $this->assertTrue($document->isImagePreview());
    }

    public function test_word_document_is_not_previewable(): void
    {
        $document = new Document([
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'filename' => 'memo.docx',
        ]);

        $this->assertFalse($document->isPreviewableInBrowser());
    }
}
