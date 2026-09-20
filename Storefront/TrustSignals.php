<?php

namespace App\GP247\Plugins\MultiVendor\Storefront;

use App\GP247\Plugins\MultiVendor\Dispute\Dispute;
use App\GP247\Plugins\MultiVendor\Models\VendorOrderShipment;
use GP247\Shop\Models\ShopOrder;
use GP247\Shop\Models\ShopOrderStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * What a shopper is told about a vendor, computed from what the marketplace
 * already recorded (S5-3).
 *
 * Two rules shape everything here:
 *
 *   1. Every figure must be one the data can actually support. There is no
 *      promised delivery date anywhere in GP247, so "on-time delivery" is not
 *      shown at all — handling time takes its place. vendor_dispute has no
 *      "vendor answered at" column, so the response FIGURE is a rate ("answered
 *      before the marketplace had to step in"), never an invented duration.
 *   2. Below a minimum sample the whole block is absent. A shop with three
 *      orders and one complaint is not a "33% dispute rate"; it is a shop nobody
 *      has enough evidence about, and publishing the number would smear it.
 *
 * Read model only: no table, no column, no write. Cached per store because the
 * shop page is public and this runs on an environment that may have no cron and
 * no queue (NFR-AVAIL-001).
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-shop-trust-signals
 * @aidlc-adr multi-vendor_shop-trust-signals
 */
final class TrustSignals
{
    /** Recent behaviour, not a lifetime record. */
    public const WINDOW_DAYS = 90;

    /** Below this many completed orders in the window, nothing is published. */
    public const MIN_ORDERS = 10;

    /** Seconds a computed block stays cached. */
    public const CACHE_TTL = 3600;

    /**
     * The published record of one shop, or null when there is not enough of it.
     *
     * @param string $storeId
     * @return array{orders:int, window_days:int, dispute_rate:float, dispute_response_rate:float|null, handling_hours:float|null, review_reply_rate:float|null}|null
     */
    public static function for(string $storeId): ?array
    {
        if ($storeId === '' || $storeId === (string) GP247_STORE_ID_GLOBAL) {
            return null;
        }

        return Cache::remember(self::cacheKey($storeId), self::CACHE_TTL, function () use ($storeId) {
            // WHY a sentinel instead of null: Cache::remember re-runs the closure
            // for a null value, so a brand-new shop would recompute on every hit.
            return self::compute($storeId) ?? ['orders' => 0];
        }) === ['orders' => 0] ? null : Cache::get(self::cacheKey($storeId));
    }

    /**
     * Drop the cached block of one store (after data that feeds it changes).
     *
     * @param string $storeId
     * @return void
     */
    public static function forget(string $storeId): void
    {
        Cache::forget(self::cacheKey($storeId));
    }

    /**
     * @param string $storeId
     * @return string
     */
    private static function cacheKey(string $storeId): string
    {
        return 'mv_trust_signals_'.$storeId;
    }

    /**
     * Compute the block, or null when the sample is too small.
     *
     * @param string $storeId
     * @return array<string, mixed>|null
     */
    private static function compute(string $storeId): ?array
    {
        $since = now()->subDays(self::WINDOW_DAYS);

        $orders = ShopOrder::where('store_id', $storeId)
            ->where('status', ShopOrderStatus::DONE)
            ->where('finish_date', '>=', $since->toDateString())
            ->count();

        if ($orders < self::MIN_ORDERS) {
            return null;
        }

        $disputes = self::disputeCounts($storeId, $since);

        return [
            'orders' => $orders,
            'window_days' => self::WINDOW_DAYS,
            'dispute_rate' => round($disputes['counted'] / $orders * 100, 1),
            // Null, not zero: "nobody complained" is not "answered 0% of the time".
            'dispute_response_rate' => $disputes['counted'] > 0
                ? round($disputes['answered'] / $disputes['counted'] * 100, 1)
                : null,
            'handling_hours' => self::medianHandlingHours($storeId, $since),
            'review_reply_rate' => self::reviewReplyRate($storeId, $since),
        ];
    }

    /**
     * Complaints of the window that count against the shop, and how many of
     * those the vendor answered before the marketplace had to step in — asked
     * through the dispute contract, so a marketplace without a dispute desk
     * simply counts zero.
     *
     * @param string $storeId
     * @param \Carbon\Carbon $since
     * @return array{counted:int, answered:int}
     */
    private static function disputeCounts(string $storeId, $since): array
    {
        return Dispute::countsForStore($storeId, $since);
    }

    /**
     * Median hours from order placed to the vendor marking it shipped, or null
     * when the shop records no shipments.
     *
     * Median rather than average on purpose: one order somebody forgot to mark
     * for a month drags an average somewhere no customer would recognise, while
     * the median still answers "how long does this shop usually take".
     *
     * @param string $storeId
     * @param \Carbon\Carbon $since
     * @return float|null
     */
    private static function medianHandlingHours(string $storeId, $since): ?float
    {
        $orderTable = (new ShopOrder)->getTable();
        $shipTable = (new VendorOrderShipment)->getTable();

        $hours = DB::connection(GP247_DB_CONNECTION)
            ->table($orderTable.' as o')
            ->join($shipTable.' as s', 's.order_id', '=', 'o.id')
            ->where('o.store_id', $storeId)
            ->where('o.status', ShopOrderStatus::DONE)
            ->where('o.finish_date', '>=', $since->toDateString())
            ->whereNotNull('s.shipped_at')
            ->selectRaw('TIMESTAMPDIFF(MINUTE, o.created_at, s.shipped_at) as minutes')
            ->orderBy('minutes')
            ->pluck('minutes')
            ->map(fn ($m) => (float) $m)
            ->filter(fn ($m) => $m >= 0)
            ->values();

        if ($hours->isEmpty()) {
            return null;
        }

        $count = $hours->count();
        $middle = (int) floor($count / 2);
        $median = $count % 2 === 1
            ? $hours[$middle]
            : ($hours[$middle - 1] + $hours[$middle]) / 2;

        return round($median / 60, 1);
    }

    /**
     * Share of the shop's published reviews in the window that it answered, or
     * null when the rating plugin is absent or nothing has been reviewed.
     *
     * Uses the ProductRating seller contract (S5-1) rather than its classes, so a
     * marketplace without that plugin simply shows one figure fewer.
     *
     * @param string $storeId
     * @param \Carbon\Carbon $since
     * @return float|null
     */
    private static function reviewReplyRate(string $storeId, $since): ?float
    {
        if (!function_exists('gp247_product_rating_review_model') || !function_exists('gp247_product_rating_seller_constrain')) {
            return null;
        }

        try {
            $query = gp247_product_rating_review_model()->newQuery();
            gp247_product_rating_seller_constrain($query, $storeId);
            $query->where('status', 1)->where('created_at', '>=', $since);

            $total = (clone $query)->count();
            if ($total === 0) {
                return null;
            }

            $answered = (clone $query)->whereNotNull('reply_content')->count();

            return round($answered / $total * 100, 1);
        } catch (\Throwable $e) {
            // A rating plugin mid-upgrade must never take the shop page down.
            return null;
        }
    }
}
