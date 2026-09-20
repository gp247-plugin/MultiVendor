<?php

namespace App\GP247\Plugins\MultiVendor\Payout;

use App\GP247\Plugins\MultiVendor\Tier\Tier;

/**
 * The free payout run's one door to the paid payout features.
 *
 * Holds the ledger vocabulary both editions share — the `kind` of a money
 * process row and which kinds are adjustments — and forwards the payout run's
 * three questions (payout account snapshot, order links, pending netting) to the
 * PayoutProvider a paid plugin registered. It also tells the free screens which
 * paid Livewire components to embed (the batch panel on the marketplace's payout
 * screen, the payout-account form on the vendor's), so those screens keep a
 * slot without ever naming a paid class.
 *
 * Resolved on every call on purpose (a `new` of a stateless class): caching
 * would hide the paid plugin being enabled or disabled inside one process.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-paid-code-location
 * @aidlc-adr multi-vendor_pro-code-relocation
 */
final class Payout
{
    /** Config path a paid plugin writes its PayoutProvider class name to. */
    public const PROVIDER_CONFIG = 'Plugins/MultiVendor.payout.provider';

    /** Config path of the paid Livewire component settling a whole payout batch (marketplace payout screen). */
    public const BATCH_PANEL_CONFIG = 'Plugins/MultiVendor.payout.batch_panel';

    /** Config path of the paid Livewire component where a vendor records its payout account (vendor payout screen). */
    public const ACCOUNT_FORM_CONFIG = 'Plugins/MultiVendor.payout.account_form';

    /** Kinds of a money-process (ledger) row. A period row is the free payout run's own. */
    public const KIND_PERIOD = 'period';
    public const KIND_CLAWBACK = 'clawback';
    public const KIND_REFUND = 'refund';
    public const KIND_REVERSAL = 'reversal';
    public const KIND_FEE = 'fee';
    /** @var string[] Rows that adjust a period rather than pay one. */
    public const ADJUSTMENT_KINDS = [self::KIND_CLAWBACK, self::KIND_REFUND, self::KIND_REVERSAL, self::KIND_FEE];

    /**
     * The registered provider, or the null provider when there is none or the
     * edition does not include the feature the caller is asking about.
     */
    public static function provider(string $feature): PayoutProvider
    {
        if (!Tier::allows($feature)) {
            return new NullPayoutProvider();
        }
        $class = config(self::PROVIDER_CONFIG);
        if (is_string($class) && $class !== '' && class_exists($class)) {
            $provider = new $class();
            if ($provider instanceof PayoutProvider) {
                return $provider;
            }
        }

        return new NullPayoutProvider();
    }

    /**
     * @see PayoutProvider::accountSnapshot()
     * @return array{method: string, masked: string}
     */
    public static function accountSnapshot(string $storeId): array
    {
        return self::provider(Tier::F_PAYOUT_ACCOUNT)->accountSnapshot($storeId);
    }

    /**
     * @see PayoutProvider::recordPeriodOrders()
     * @param iterable<\GP247\Shop\Models\ShopOrder> $orders
     */
    public static function recordPeriodOrders(string $processId, string $storeId, string $currency, int $vendorShare, iterable $orders): int
    {
        return self::provider(Tier::F_CLAWBACK)->recordPeriodOrders($processId, $storeId, $currency, $vendorShare, $orders);
    }

    /** @see PayoutProvider::netPending() */
    public static function netPending(string $storeId, string $currency, string $intoProcessId): float
    {
        return self::provider(Tier::F_CLAWBACK)->netPending($storeId, $currency, $intoProcessId);
    }

    /** Livewire class of the paid batch panel to embed, or null (the screen shows its upgrade hint instead). */
    public static function batchPanel(): ?string
    {
        return self::component(self::BATCH_PANEL_CONFIG, Tier::F_PAYOUT_BATCH);
    }

    /** Livewire class of the paid payout-account form to embed, or null. */
    public static function accountForm(): ?string
    {
        return self::component(self::ACCOUNT_FORM_CONFIG, Tier::F_PAYOUT_ACCOUNT);
    }

    /** Human label of a ledger row kind (i18n); '' for a plain period row. */
    public static function kindLabel(?string $kind): string
    {
        $kind = (string) $kind;
        if ($kind === '' || $kind === self::KIND_PERIOD) {
            return '';
        }

        return (string) gp247_language_render('multi_vendor.clawback.kind_'.$kind);
    }

    /** Whether a ledger row adjusts a period rather than pays one. */
    public static function isAdjustment(?string $kind): bool
    {
        return in_array((string) $kind, self::ADJUSTMENT_KINDS, true);
    }

    /**
     * A paid Livewire component registered under $configKey, when the edition
     * allows $feature and the class is loadable.
     */
    private static function component(string $configKey, string $feature): ?string
    {
        if (!Tier::allows($feature)) {
            return null;
        }
        $class = config($configKey);

        return is_string($class) && $class !== '' && class_exists($class) ? $class : null;
    }
}
