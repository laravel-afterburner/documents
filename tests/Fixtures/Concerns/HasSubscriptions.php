<?php

namespace Afterburner\Subscriptions\Concerns;

/**
 * Test double for the subscriptions package HasSubscriptions trait.
 */
trait HasSubscriptions
{
    public function hasEntitlement(string $slug): bool
    {
        if ($slug === 'documents') {
            return (bool) ($this->documents_entitled ?? true);
        }

        return false;
    }

    public function onGenericTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function withinEntitlementLimit(string $key, int|float $current): bool
    {
        if ($key === 'max_storage_gb') {
            return (bool) ($this->storage_within_limit ?? true);
        }

        return true;
    }
}
