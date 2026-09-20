<?php

namespace App\GP247\Plugins\MultiVendor\Plans;

use Illuminate\Support\Collection;

/**
 * The plan shelf that is not there: no plans, no subscription, no cap, no
 * plan rate. Every free slot renders its "no plan" state and the commission
 * policy falls through to the marketplace rate.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-paid-code-location
 * @aidlc-adr multi-vendor_pro-code-relocation
 */
final class NullPlanProvider implements PlanProvider
{
    public function enabled(): bool
    {
        return false;
    }

    public function plans(): Collection
    {
        return collect();
    }

    public function current(string $storeId): ?object
    {
        return null;
    }

    public function planOf(string $storeId): ?object
    {
        return null;
    }

    public function assign(string $storeId, string $planId, $adminId = null, string $note = ''): ?object
    {
        return null;
    }

    public function cancel(string $storeId): int
    {
        return 0;
    }

    public function summary(string $storeId): ?array
    {
        return null;
    }

    public function maxProducts(string $storeId): ?int
    {
        return null;
    }

    public function productLimitReached(string $storeId): bool
    {
        return false;
    }

    public function rateFor(string $storeId): ?int
    {
        return null;
    }
}
