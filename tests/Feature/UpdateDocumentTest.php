<?php

namespace Afterburner\Documents\Tests\Feature;

use Afterburner\Documents\Actions\UpdateDocument;
use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class UpdateDocumentTest extends TestCase
{
    public function test_replacing_file_overwrites_existing_storage_path(): void
    {
        [$user, $team] = $this->createTeamWithUser();

        $document = Document::query()->create([
            'team_id' => $team->id,
            'folder_id' => null,
            'name' => 'Cookie Recipe',
            'filename' => 'Chocolate Chip Cookie Recipe.pdf',
            'mime_type' => 'application/pdf',
            'size' => 8,
            'storage_path' => 'documents/1/2026/05/5/chocolate-chip-cookie-recipe.pdf',
            'upload_status' => 'completed',
            'upload_progress' => 100,
            'uploaded_by' => $user->id,
        ]);

        Storage::disk('r2')->put($document->storage_path, 'original');

        app(UpdateDocument::class)->execute(
            $document,
            [
                'filename' => 'Ginger Snaps & Coconut Crisps.pdf',
                'mime_type' => 'application/pdf',
                'size' => 7,
            ],
            'updated',
            $user
        );

        $document->refresh();

        $this->assertSame('documents/1/2026/05/5/chocolate-chip-cookie-recipe.pdf', $document->storage_path);
        $this->assertSame('Ginger Snaps & Coconut Crisps.pdf', $document->filename);
        $this->assertSame('updated', Storage::disk('r2')->get($document->storage_path));
        $this->assertTrue(Storage::disk('r2')->exists($document->storage_path));
    }
}
