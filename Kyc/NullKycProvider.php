<?php

namespace App\GP247\Plugins\MultiVendor\Kyc;

use Illuminate\Support\Collection;

/**
 * The answer when no identity-verification provider is installed: nothing is
 * required, nobody is blocked, nobody is verified, nothing waits for review.
 *
 * WHY a real class rather than null checks at every call site: the free plugin
 * asks about KYC from product approval, the payout run, two storefront badges
 * and the vendor dashboard. One object that always says "no" keeps every one
 * of those paths straight-line code.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-paid-code-location
 * @aidlc-adr multi-vendor_pro-code-relocation
 */
final class NullKycProvider implements KycProvider
{
    public function required(): bool
    {
        return false;
    }

    public function blocks(string $storeId): bool
    {
        return false;
    }

    public function payoutStatusFor(string $storeId): string
    {
        return Kyc::PAYOUT_PROCESSING;
    }

    public function isVerified(string $storeId): bool
    {
        return false;
    }

    public function verifiedIds(array $storeIds): array
    {
        return [];
    }

    public function statusOf(string $storeId): ?string
    {
        return null;
    }

    public function pending(): Collection
    {
        return collect();
    }

    public function decrypted(object $profile): array
    {
        return [];
    }

    public function approve(string $storeId, $adminId = null): bool
    {
        return false;
    }

    public function reject(string $storeId, string $reason, $adminId = null): bool
    {
        return false;
    }
}
