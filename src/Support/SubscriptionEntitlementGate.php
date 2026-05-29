<?php

namespace Afterburner\Documents\Support;

use Afterburner\Documents\Models\Document;
use App\Models\Team;

final class SubscriptionEntitlementGate
{
    public const FEATURE_SLUG = 'documents';

    public const STORAGE_LIMIT_KEY = 'max_storage_gb';

    /**
     * Whether the team may use documents features (plan feature slug).
     */
    public static function allows(Team $team, string $feature = self::FEATURE_SLUG): bool
    {
        if (! self::shouldEnforceForTeam($team)) {
            return true;
        }

        if (self::teamOnGenericTrial($team)) {
            return true;
        }

        return $team->hasEntitlement($feature);
    }

    /**
     * Whether the team's current usage is within a numeric plan limit.
     */
    public static function withinLimit(Team $team, string $limitKey, int|float $current): bool
    {
        if (! self::shouldEnforceForTeam($team)) {
            return true;
        }

        if (self::teamOnGenericTrial($team)) {
            return true;
        }

        return $team->withinEntitlementLimit($limitKey, $current);
    }

    /**
     * Whether an upload of the given size would stay within the team's storage entitlement.
     */
    public static function allowsStorageForUpload(Team $team, int $additionalBytes): bool
    {
        return self::withinLimit(
            $team,
            self::STORAGE_LIMIT_KEY,
            self::teamStorageGigabytesUsed($team, $additionalBytes)
        );
    }

    public static function teamStorageGigabytesUsed(Team $team, int $additionalBytes = 0): float
    {
        $bytes = (int) Document::query()
            ->where('team_id', $team->id)
            ->sum('size');

        return ($bytes + $additionalBytes) / (1024 ** 3);
    }

    public static function shouldEnforceForTeam(Team $team): bool
    {
        if (! self::subscriptionsPackageEnabled()) {
            return false;
        }

        return self::teamHasSubscriptions($team);
    }

    public static function subscriptionsPackageEnabled(): bool
    {
        return (bool) config('afterburner-subscriptions.enabled', false);
    }

    public static function teamHasSubscriptions(Team $team): bool
    {
        return in_array(
            'Afterburner\\Subscriptions\\Concerns\\HasSubscriptions',
            class_uses_recursive($team),
            true
        );
    }

    public static function teamOnGenericTrial(Team $team): bool
    {
        return method_exists($team, 'onGenericTrial') && $team->onGenericTrial();
    }
}
