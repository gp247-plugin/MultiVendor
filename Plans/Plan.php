<?php

namespace App\GP247\Plugins\MultiVendor\Plans;

use App\GP247\Plugins\MultiVendor\Tier\Tier;
use Illuminate\Support\Collection;

/**
 * The marketplace's one door to vendor plans.
 *
 * Holds the vocabulary both editions share (fee periods, subscription
 * statuses) and forwards every question to the PlanProvider a paid plugin
 * registered under `config('Plugins/MultiVendor.plans.provider')`. With no
 * provider, or an edition without plans, NullPlanProvider answers.
 *
 * Resolved on every call on purpose (a `new` of a stateless class): caching
 * would hide the paid plugin being enabled or disabled inside one process.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-paid-code-location
 * @aidlc-adr multi-vendor_pro-code-relocation
 */
final class Plan
{
    /** Config path a paid plugin writes its PlanProvider class name to. */
    public const PROVIDER_CONFIG = 'Plugins/MultiVendor.plans.provider';

    public const PERIOD_NONE = 'none';
    public const PERIOD_MONTH = 'month';
    public const PERIOD_YEAR = 'year';
    public const PERIODS = [self::PERIOD_NONE, self::PERIOD_MONTH, self::PERIOD_YEAR];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELED = 'canceled';

    /**
     * The registered provider, or the null provider when there is none or the
     * edition does not include plans.
     */
    public static function provider(): PlanProvider
    {
        if (!Tier::allows(Tier::F_VENDOR_PLANS)) {
            return new NullPlanProvider();
        }
        $class = config(self::PROVIDER_CONFIG);
        if (is_string($class) && $class !== '' && class_exists($class)) {
            $provider = new $class();
            if ($provider instanceof PlanProvider) {
                return $provider;
            }
        }

        return new NullPlanProvider();
    }

    /** @see PlanProvider::enabled() */
    public static function enabled(): bool
    {
        return self::provider()->enabled();
    }

    /** @see PlanProvider::plans() */
    public static function plans(): Collection
    {
        return self::provider()->plans();
    }

    /** @see PlanProvider::current() */
    public static function current(string $storeId): ?object
    {
        return self::provider()->current($storeId);
    }

    /** @see PlanProvider::planOf() */
    public static function planOf(string $storeId): ?object
    {
        return self::provider()->planOf($storeId);
    }

    /** @see PlanProvider::assign() */
    public static function assign(string $storeId, string $planId, $adminId = null, string $note = ''): ?object
    {
        return self::provider()->assign($storeId, $planId, $adminId, $note);
    }

    /** @see PlanProvider::cancel() */
    public static function cancel(string $storeId): int
    {
        return self::provider()->cancel($storeId);
    }

    /**
     * @see PlanProvider::summary()
     * @return array{name:string, max:?int, count:int, period_end:?string, fee:string, expired:bool}|null
     */
    public static function summary(string $storeId): ?array
    {
        return self::provider()->summary($storeId);
    }

    /** @see PlanProvider::maxProducts() */
    public static function maxProducts(string $storeId): ?int
    {
        return self::provider()->maxProducts($storeId);
    }

    /** @see PlanProvider::productLimitReached() */
    public static function productLimitReached(string $storeId): bool
    {
        return self::provider()->productLimitReached($storeId);
    }

    /** @see PlanProvider::rateFor() */
    public static function rateFor(string $storeId): ?int
    {
        return self::provider()->rateFor($storeId);
    }

    /** Human label (i18n) of a period / status key. */
    public static function label(string $key): string
    {
        return (string) gp247_language_render('multi_vendor.plan.'.$key);
    }
}
