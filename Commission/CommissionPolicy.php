<?php

namespace App\GP247\Plugins\MultiVendor\Commission;

use App\GP247\Plugins\MultiVendor\Tier\Tier;
use GP247\Core\Models\AdminConfig;

/**
 * Commission the marketplace keeps on a vendor store's completed sales.
 *
 * Resolution (S1-1, per-vendor commission): a store-scoped override row in
 * admin_config (`MultiVendor_commission`, store_id = the vendor store) wins;
 * without one the marketplace-wide rate (store_id = GLOBAL) applies. This is the
 * same store-scoped config mechanism core uses for plugin settings — no new
 * column on admin_store, no migration, and deleting a store already purges its
 * admin_config rows.
 *
 * WHY the override is read with a direct query instead of gp247_config(): the
 * per-store config cache is a static array filled once per request; the root
 * admin edits an override and the very same request re-renders the effective
 * rate, so the read must see the write. The marketplace rate keeps using the
 * cached global reader (it does not change inside those requests).
 *
 * Money standard (S1-5, NFR-mv-money-minor-unit): the payout ledger stores
 * decimal(15,2) like shop_order and amount() rounds to the CURRENCY precision
 * (VND 0, USD 2 — ShopCurrency.precision), so no fraction is lost and the ledger
 * reconciles with the orders it was built from. The 1.0 floor-on-integer is gone.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-per-vendor-commission
 * @aidlc-adr multi-vendor_per-vendor-commission-storage
 */
final class CommissionPolicy
{
    /** admin_config key — shared by the GLOBAL row and the per-store override rows. */
    public const CONFIG_KEY = 'MultiVendor_commission';

    /**
     * Marketplace-wide commission rate (%), 0 when unset. Clamped to 0–100.
     */
    public static function marketplaceRate(): int
    {
        return self::clamp(gp247_config_global(self::CONFIG_KEY, 0));
    }

    /**
     * The store's own override rate (%), or null when the store follows the
     * marketplace rate.
     */
    public static function overrideFor(string $storeId): ?int
    {
        if ($storeId === '' || $storeId === (string) GP247_STORE_ID_GLOBAL) {
            return null;
        }
        // S4-1: per-vendor rates are Pro. Free reads the marketplace rate; stored
        // overrides are kept untouched so a later upgrade picks them up again.
        if (!Tier::allows(Tier::F_PER_VENDOR_COMMISSION)) {
            return null;
        }
        $value = AdminConfig::where('key', self::CONFIG_KEY)
            ->where('store_id', $storeId)
            ->value('value');

        return $value === null ? null : self::clamp($value);
    }

    public static function isOverridden(string $storeId): bool
    {
        return self::overrideFor($storeId) !== null;
    }

    /**
     * Effective commission rate (%) for a store: override first, marketplace otherwise.
     */
    public static function rateFor(string $storeId): int
    {
        // S3-4: an explicit store override wins, then the store's plan rate, then the marketplace.
        return self::overrideFor($storeId) ?? \App\GP247\Plugins\MultiVendor\Plans\Plan::rateFor($storeId) ?? self::marketplaceRate();
    }

    /**
     * Set (or clear with null) a store's override. Clamped to 0–100.
     *
     * The row mirrors the GLOBAL seed row (same group/code/detail) so the core
     * config screens and the store purge on delete treat it like any plugin setting.
     */
    public static function setOverride(string $storeId, ?int $rate): void
    {
        if ($storeId === '' || $storeId === (string) GP247_STORE_ID_GLOBAL) {
            return;
        }
        $query = AdminConfig::where('key', self::CONFIG_KEY)->where('store_id', $storeId);

        if ($rate === null) {
            $query->delete();

            return;
        }

        $stored = (string) self::clamp($rate);
        if ($query->exists()) {
            $query->update(['value' => $stored]);

            return;
        }
        AdminConfig::insert([
            'group'    => '',
            'code'     => 'MultiVendor_config',
            'key'      => self::CONFIG_KEY,
            'sort'     => 0,
            'store_id' => $storeId,
            'value'    => $stored,
            'detail'   => 'multi_vendor.'.self::CONFIG_KEY,
        ]);
    }

    /** Share (%) the vendor receives for a given commission rate. */
    public static function vendorShare(int $rate): int
    {
        return 100 - self::clamp($rate);
    }

    /**
     * Amount owed to the vendor for a period total at a commission rate, rounded
     * half-up to the currency's precision (2 when the currency is unknown).
     */
    public static function amount(float $total, int $rate, ?string $currency = null): float
    {
        $precision = self::precision($currency);

        return round(self::vendorShare($rate) * $total / 100, $precision);
    }

    /** @var array<string,int> precision per currency code, cached per request */
    private static array $precisionCache = [];

    /**
     * Decimal places of a currency (ShopCurrency.precision); 2 when unknown.
     */
    public static function precision(?string $currency): int
    {
        $code = strtoupper(trim((string) $currency));
        if ($code === '') {
            return 2;
        }
        if (!array_key_exists($code, self::$precisionCache)) {
            $p = null;
            try {
                $p = \GP247\Shop\Models\ShopCurrency::where('code', $code)->value('precision');
            } catch (\Throwable $e) {
                $p = null;
            }
            self::$precisionCache[$code] = $p === null ? 2 : max(0, min(4, (int) $p));
        }

        return self::$precisionCache[$code];
    }

    /** Clamp any raw config value to an integer percentage 0–100. */
    private static function clamp($value): int
    {
        $n = is_numeric($value) ? (int) $value : 0;

        return max(0, min(100, $n));
    }
}
