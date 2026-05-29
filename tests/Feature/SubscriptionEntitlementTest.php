<?php

namespace Afterburner\Documents\Tests\Feature;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Tests\TestCase;
use App\Models\SubscribableTeam;
use App\Models\Team;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;

class SubscriptionEntitlementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            EnsureEmailIsVerified::class,
        ]);
        Notification::fake();

        Route::bind('team', function (string $value) {
            return SubscribableTeam::query()->find($value)
                ?? Team::query()->findOrFail($value);
        });
    }

    public function test_team_access_allowed_when_subscriptions_disabled(): void
    {
        config(['afterburner-subscriptions.enabled' => false]);

        [$user, $team] = $this->createSubscribableTeamWithUser([
            'documents_entitled' => false,
        ]);

        $this->assertTrue(Gate::forUser($user)->check('documents.access-team', $team));
    }

    public function test_team_access_allowed_when_subscriptions_not_installed(): void
    {
        config(['afterburner-subscriptions.enabled' => true]);

        [$user, $team] = $this->createTeamWithUser();

        $this->assertTrue(Gate::forUser($user)->check('documents.access-team', $team));
    }

    public function test_team_access_allowed_on_generic_trial_without_plan_feature(): void
    {
        config(['afterburner-subscriptions.enabled' => true]);

        [$user, $team] = $this->createSubscribableTeamWithUser([
            'documents_entitled' => false,
            'trial_ends_at' => now()->addDay(),
        ]);

        $this->assertTrue(Gate::forUser($user)->check('documents.access-team', $team));
    }

    public function test_team_access_denied_when_trial_expired_without_entitlement(): void
    {
        config(['afterburner-subscriptions.enabled' => true]);

        [$user, $team] = $this->createSubscribableTeamWithUser([
            'documents_entitled' => false,
            'trial_ends_at' => null,
        ]);

        $this->assertFalse(Gate::forUser($user)->check('documents.access-team', $team));
    }

    public function test_team_access_allowed_when_entitled(): void
    {
        config(['afterburner-subscriptions.enabled' => true]);

        [$user, $team] = $this->createSubscribableTeamWithUser([
            'documents_entitled' => true,
            'trial_ends_at' => null,
        ]);

        $this->assertTrue(Gate::forUser($user)->check('documents.access-team', $team));
    }

    public function test_create_policy_denied_without_entitlement_after_trial(): void
    {
        config(['afterburner-subscriptions.enabled' => true]);

        [$user, $team] = $this->createSubscribableTeamWithUser([
            'documents_entitled' => false,
            'trial_ends_at' => null,
        ]);

        $this->assertFalse(Gate::forUser($user)->check('create', [Document::class, $team]));
    }

    public function test_create_policy_allowed_with_entitlement_and_permission(): void
    {
        config(['afterburner-subscriptions.enabled' => true]);

        [$user, $team] = $this->createSubscribableTeamWithUser([
            'documents_entitled' => true,
            'trial_ends_at' => null,
        ]);

        $this->assertTrue(Gate::forUser($user)->check('create', [Document::class, $team]));
    }

    public function test_upload_denied_when_storage_limit_exceeded(): void
    {
        config(['afterburner-subscriptions.enabled' => true]);

        [$user, $team] = $this->createSubscribableTeamWithUser([
            'documents_entitled' => true,
            'trial_ends_at' => null,
            'storage_within_limit' => false,
        ]);

        $file = UploadedFile::fake()->create('notes.txt', 2, 'text/plain');
        file_put_contents($file->getRealPath(), 'hi');

        $this->actingAs($user)
            ->post(route('teams.documents.upload.process', $team), ['file' => $file])
            ->assertStatus(403)
            ->assertSee('Storage limit exceeded');
    }

    public function test_view_policy_allowed_on_trial_without_entitlement(): void
    {
        config(['afterburner-subscriptions.enabled' => true]);

        [$user, $team] = $this->createSubscribableTeamWithUser([
            'documents_entitled' => false,
            'trial_ends_at' => now()->addDay(),
        ]);

        $document = Document::query()->create([
            'team_id' => $team->id,
            'name' => 'notes',
            'filename' => 'notes.txt',
            'mime_type' => 'text/plain',
            'size' => 10,
            'storage_path' => 'documents/notes.txt',
            'upload_status' => 'completed',
            'upload_progress' => 100,
            'uploaded_by' => $user->id,
        ]);
        $document->setRelation('team', $team);

        $this->assertTrue(Gate::forUser($user)->check('view', $document));
    }
}
