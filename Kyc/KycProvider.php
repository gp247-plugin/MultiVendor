<?php

namespace App\GP247\Plugins\MultiVendor\Kyc;

use Illuminate\Support\Collection;

/**
 * What the marketplace needs to know about a store's identity verification.
 *
 * The free plugin asks these questions in several places — product approval,
 * the payout run, the verified badge on the storefront, the vendor dashboard —
 * but it does not answer them: the identity profile, its table, its screen and
 * its review flow ship with the paid package. A paid plugin registers an
 * implementation under `config('Plugins/MultiVendor.kyc.provider')`; without
 * one, NullKycProvider answers "no verification on this marketplace".
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-paid-code-location
 * @aidlc-adr multi-vendor_pro-code-relocation
 */
interface KycProvider
{
    /** Whether this marketplace requires identity verification at all. */
    public function required(): bool;

    /** Whether the store is blocked (verification required and not granted). */
    public function blocks(string $storeId): bool;

    /** Ledger status a new payout row of this store should get: pending | processing. */
    public function payoutStatusFor(string $storeId): string;

    /** Whether the store's identity was approved. */
    public function isVerified(string $storeId): bool;

    /**
     * Subset of the given store ids that are verified (one query, for lists).
     *
     * @param  array<int, string> $storeIds
     * @return array<int, string>
     */
    public function verifiedIds(array $storeIds): array;

    /** The store's profile status (pending | approved | rejected) or null when none. */
    public function statusOf(string $storeId): ?string;

    /** Profiles waiting for review, newest first (objects exposing store_id, status, submitted_at). */
    public function pending(): Collection;

    /**
     * Plain-text view of a pending profile for the reviewer.
     *
     * @param  object $profile One item of pending().
     * @return array<string, string>
     */
    public function decrypted(object $profile): array;

    /** Approve a pending profile. */
    public function approve(string $storeId, $adminId = null): bool;

    /** Reject a pending profile with a reason. */
    public function reject(string $storeId, string $reason, $adminId = null): bool;
}
