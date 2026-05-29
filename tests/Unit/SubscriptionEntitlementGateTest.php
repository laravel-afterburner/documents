<?php

namespace Afterburner\Documents\Tests\Unit;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Support\SubscriptionEntitlementGate;
use Afterburner\Documents\Tests\TestCase;

class SubscriptionEntitlementGateTest extends TestCase
{
    public function test_allows_when_subscriptions_disabled(): void
    {
        config(['afterburner-subscriptions.enabled' => false]);

        [, $team] = $this->createSubscribableTeamWithUser([
            'documents_entitled' => false,
        ]);

        $this->assertTrue(SubscriptionEntitlementGate::allows($team));
    }

    public function test_allows_when_subscriptions_not_installed_on_team(): void
    {
        config(['afterburner-subscriptions.enabled' => true]);

        [, $team] = $this->createTeamWithUser();

        $this->assertTrue(SubscriptionEntitlementGate::allows($team));
    }

    public function test_allows_when_team_on_generic_trial(): void
    {
        config(['afterburner-subscriptions.enabled' => true]);

        [, $team] = $this->createSubscribableTeamWithUser([
            'documents_entitled' => false,
            'trial_ends_at' => now()->addDay(),
        ]);

        $this->assertTrue(SubscriptionEntitlementGate::allows($team));
    }

    public function test_denies_when_subscriptions_active_without_entitlement(): void
    {
        config(['afterburner-subscriptions.enabled' => true]);

        [, $team] = $this->createSubscribableTeamWithUser([
            'documents_entitled' => false,
            'trial_ends_at' => null,
        ]);

        $this->assertFalse(SubscriptionEntitlementGate::allows($team));
    }

    public function test_allows_when_subscriptions_active_with_entitlement(): void
    {
        config(['afterburner-subscriptions.enabled' => true]);

        [, $team] = $this->createSubscribableTeamWithUser([
            'documents_entitled' => true,
            'trial_ends_at' => null,
        ]);

        $this->assertTrue(SubscriptionEntitlementGate::allows($team));
    }

    public function test_storage_limit_bypassed_when_subscriptions_disabled(): void
    {
        config(['afterburner-subscriptions.enabled' => false]);

        [, $team] = $this->createSubscribableTeamWithUser([
            'storage_within_limit' => false,
        ]);

        $this->assertTrue(SubscriptionEntitlementGate::allowsStorageForUpload($team, 1024 ** 3));
    }

    public function test_storage_limit_denied_when_exceeded(): void
    {
        config(['afterburner-subscriptions.enabled' => true]);

        [, $team] = $this->createSubscribableTeamWithUser([
            'storage_within_limit' => false,
            'trial_ends_at' => null,
        ]);

        $this->assertFalse(SubscriptionEntitlementGate::allowsStorageForUpload($team, 0));
    }

    public function test_team_storage_gigabytes_includes_existing_documents(): void
    {
        [, $team] = $this->createTeamWithUser();

        Document::query()->create([
            'team_id' => $team->id,
            'name' => 'sample',
            'filename' => 'sample.txt',
            'mime_type' => 'text/plain',
            'size' => 1024 ** 3,
            'storage_path' => 'documents/sample.txt',
            'upload_status' => 'completed',
            'upload_progress' => 100,
            'uploaded_by' => $team->user_id,
        ]);

        $this->assertEqualsWithDelta(1.0, SubscriptionEntitlementGate::teamStorageGigabytesUsed($team), 0.001);
    }
}
