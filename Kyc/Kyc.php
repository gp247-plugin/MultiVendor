<?php

namespace App\GP247\Plugins\MultiVendor\Kyc;

use App\GP247\Plugins\MultiVendor\Tier\Tier;
use Illuminate\Support\Collection;

/**
 * The marketplace's one door to identity verification (KYC).
 *
 * Every place in the free plugin that needs to know whether a store is
 * verified or blocked asks here. The answers come from whatever KycProvider a
 * paid plugin registered under `config('Plugins/MultiVendor.kyc.provider')`;
 * when there is none, or the edition does not include KYC, NullKycProvider
 * answers and the marketplace behaves as if verification did not exist.
 *
 * The provider is resolved on every call on purpose: it is a `new` of a
 * stateless class, and caching it would make the edition switch (Pro plugin
 * enabled or disabled) invisible inside one process.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-paid-code-location
 * @aidlc-adr multi-vendor_pro-code-relocation
 */
final class Kyc
{
    /** Marketplace setting (GLOBAL): unverified stores are blocked from approval and payout. */
    public const CONFIG_KEY = 'MultiVendor_kyc_required';

    /** Config path a paid plugin writes its KycProvider class name to. */
    public const PROVIDER_CONFIG = 'Plugins/MultiVendor.kyc.provider';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /** Ledger statuses the payout run gives a new period row. */
    public const PAYOUT_PENDING = 'pending';
    public const PAYOUT_PROCESSING = 'processing';

    /**
     * The registered provider, or the null provider when there is none or the
     * edition does not include KYC.
     */
    public static function provider(): KycProvider
    {
        if (!Tier::allows(Tier::F_KYC)) {
            return new NullKycProvider();
        }
        $class = config(self::PROVIDER_CONFIG);
        if (is_string($class) && $class !== '' && class_exists($class)) {
            $provider = new $class();
            if ($provider instanceof KycProvider) {
                return $provider;
            }
        }

        return new NullKycProvider();
    }

    /** @see KycProvider::required() */
    public static function required(): bool
    {
        return self::provider()->required();
    }

    /** @see KycProvider::blocks() */
    public static function blocks(string $storeId): bool
    {
        return self::provider()->blocks($storeId);
    }

    /** @see KycProvider::payoutStatusFor() */
    public static function payoutStatusFor(string $storeId): string
    {
        return self::provider()->payoutStatusFor($storeId);
    }

    /** @see KycProvider::isVerified() */
    public static function isVerified(string $storeId): bool
    {
        return self::provider()->isVerified($storeId);
    }

    /**
     * @see KycProvider::verifiedIds()
     * @param  array<int, string> $storeIds
     * @return array<int, string>
     */
    public static function verifiedIds(array $storeIds): array
    {
        return self::provider()->verifiedIds($storeIds);
    }

    /** @see KycProvider::statusOf() */
    public static function statusOf(string $storeId): ?string
    {
        return self::provider()->statusOf($storeId);
    }

    /** @see KycProvider::pending() */
    public static function pending(): Collection
    {
        return self::provider()->pending();
    }

    /**
     * @see KycProvider::decrypted()
     * @return array<string, string>
     */
    public static function decrypted(object $profile): array
    {
        return self::provider()->decrypted($profile);
    }

    /** @see KycProvider::approve() */
    public static function approve(string $storeId, $adminId = null): bool
    {
        return self::provider()->approve($storeId, $adminId);
    }

    /** @see KycProvider::reject() */
    public static function reject(string $storeId, string $reason, $adminId = null): bool
    {
        return self::provider()->reject($storeId, $reason, $adminId);
    }
}
