<?php

namespace Afterburner\Documents\Tests\Feature;

use Afterburner\Documents\Actions\LinkDocument;
use Afterburner\Documents\Actions\UnlinkDocument;
use Afterburner\Documents\Concerns\HasDocumentLinks;
use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class LinkDocumentTest extends TestCase
{
    public function test_link_and_unlink_document_to_custom_model(): void
    {
        Gate::before(fn () => true);

        [$user, $team] = $this->createTeamWithUser();

        $linkable = new class extends Model
        {
            use HasDocumentLinks;

            protected $table = 'teams';

            public $timestamps = false;

            protected $guarded = [];
        };

        $linkable->id = $team->id;
        $linkable->team_id = $team->id;

        $document = Document::query()->create([
            'team_id' => $team->id,
            'name' => 'Policy',
            'filename' => 'policy.pdf',
            'mime_type' => 'application/pdf',
            'size' => 512,
            'storage_path' => 'teams/'.$team->id.'/policy.pdf',
            'upload_status' => 'completed',
            'uploaded_by' => $user->id,
        ]);

        app(LinkDocument::class)->execute($document, $linkable, $user);

        $this->assertTrue($linkable->linkedDocuments()->where('documents.id', $document->id)->exists());

        app(UnlinkDocument::class)->execute($document, $linkable, $user);

        $this->assertFalse($linkable->linkedDocuments()->where('documents.id', $document->id)->exists());
    }
}
