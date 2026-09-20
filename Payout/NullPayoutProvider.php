<?php

namespace App\GP247\Plugins\MultiVendor\Payout;

/**
 * The payout extras that are not installed: no payout account to snapshot, no
 * order links to remember, nothing pending to net. The free payout run books
 * the period row exactly as it did before any paid feature existed.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-paid-code-location
 * @aidlc-adr multi-vendor_pro-code-relocation
 */
final class NullPayoutProvider implements PayoutProvider
{
    public function accountSnapshot(string $storeId): array
    {
        return ['method' => '', 'masked' => ''];
    }

    public function recordPeriodOrders(string $processId, string $storeId, string $currency, int $vendorShare, iterable $orders): int
    {
        return 0;
    }

    public function netPending(string $storeId, string $currency, string $intoProcessId): float
    {
        return 0.0;
    }
}
