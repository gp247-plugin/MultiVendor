<?php

namespace App\GP247\Plugins\MultiVendor\Dispute;

use GP247\Shop\Models\ShopOrder;

/**
 * The dispute desk that is not there.
 *
 * Every caller in the free plugin keeps working against this object: the
 * storefront hook renders nothing (`eligibility()` says `disabled`), the vendor
 * order screen shows no panel (`forOrder()` is null), the trust strip counts
 * zero complaints. Nothing throws, nothing reaches a table that may not exist.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-paid-code-location
 * @aidlc-adr multi-vendor_pro-code-relocation
 */
final class NullDisputeProvider implements DisputeProvider
{
    public function enabled(): bool
    {
        return false;
    }

    public function windowDays(): int
    {
        return Dispute::DEFAULT_WINDOW_DAYS;
    }

    public function vendorDays(): int
    {
        return Dispute::DEFAULT_VENDOR_DAYS;
    }

    public function eligibility(ShopOrder $order, string $customerId): ?string
    {
        return 'disabled';
    }

    public function refundable(ShopOrder $order): float
    {
        return 0.0;
    }

    public function open(ShopOrder $order, string $customerId, string $type, string $reason, ?float $requestedAmount): object
    {
        throw new \InvalidArgumentException('disabled');
    }

    public function forOrder(string $orderId): ?object
    {
        return null;
    }

    public function withdraw(object $dispute, string $customerId): bool
    {
        return false;
    }

    public function vendorAccept(object $dispute, float $amount, string $note = ''): bool
    {
        return false;
    }

    public function vendorReject(object $dispute, string $response): bool
    {
        return false;
    }

    public function escalateOverdue(): int
    {
        return 0;
    }

    public function countsForStore(string $storeId, $since): array
    {
        return ['counted' => 0, 'answered' => 0];
    }
}
