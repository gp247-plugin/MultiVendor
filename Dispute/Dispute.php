<?php

namespace App\GP247\Plugins\MultiVendor\Dispute;

use App\GP247\Plugins\MultiVendor\Tier\Tier;
use GP247\Shop\Models\ShopOrder;

/**
 * The marketplace's one door to the dispute desk.
 *
 * Holds the vocabulary both editions share — setting keys, dispute types,
 * statuses, resolutions, minimum lengths — and forwards every question to the
 * DisputeProvider a paid plugin registered under
 * `config('Plugins/MultiVendor.dispute.provider')`. With no provider, or an
 * edition without disputes, NullDisputeProvider answers.
 *
 * Resolved on every call on purpose (a `new` of a stateless class): caching
 * would hide the paid plugin being enabled or disabled inside one process.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-paid-code-location
 * @aidlc-adr multi-vendor_pro-code-relocation
 */
final class Dispute
{
    /** Config path a paid plugin writes its DisputeProvider class name to. */
    public const PROVIDER_CONFIG = 'Plugins/MultiVendor.dispute.provider';

    /** Days after the order finished during which a customer may still open a dispute. */
    public const CONFIG_WINDOW_DAYS = 'MultiVendor_dispute_window_days';
    public const DEFAULT_WINDOW_DAYS = 14;

    /** Days the vendor has to answer before the dispute escalates to the marketplace. */
    public const CONFIG_VENDOR_DAYS = 'MultiVendor_dispute_vendor_days';
    public const DEFAULT_VENDOR_DAYS = 3;

    public const TYPE_NOT_RECEIVED = 'not_received';
    public const TYPE_DAMAGED = 'damaged';
    public const TYPE_WRONG_ITEM = 'wrong_item';
    public const TYPE_REFUND_REQUEST = 'refund_request';
    public const TYPE_OTHER = 'other';
    public const TYPES = [self::TYPE_NOT_RECEIVED, self::TYPE_DAMAGED, self::TYPE_WRONG_ITEM, self::TYPE_REFUND_REQUEST, self::TYPE_OTHER];

    public const STATUS_OPEN = 'open';
    public const STATUS_ESCALATED = 'escalated';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_WITHDRAWN = 'withdrawn';
    /** @var string[] Statuses that still block a new dispute on the same order. */
    public const LIVE_STATUSES = [self::STATUS_OPEN, self::STATUS_ESCALATED];

    public const RESOLUTION_REFUND_FULL = 'refund_full';
    public const RESOLUTION_REFUND_PARTIAL = 'refund_partial';
    public const RESOLUTION_REJECT = 'reject';
    public const RESOLUTIONS = [self::RESOLUTION_REFUND_FULL, self::RESOLUTION_REFUND_PARTIAL, self::RESOLUTION_REJECT];

    /** Minimum length of a customer's reason / a vendor's or marketplace's rejection note. */
    public const MIN_REASON = 10;
    public const MIN_NOTE = 5;

    /**
     * The registered provider, or the null provider when there is none or the
     * edition does not include disputes.
     */
    public static function provider(): DisputeProvider
    {
        if (!Tier::allows(Tier::F_DISPUTE)) {
            return new NullDisputeProvider();
        }
        $class = config(self::PROVIDER_CONFIG);
        if (is_string($class) && $class !== '' && class_exists($class)) {
            $provider = new $class();
            if ($provider instanceof DisputeProvider) {
                return $provider;
            }
        }

        return new NullDisputeProvider();
    }

    /** @see DisputeProvider::enabled() */
    public static function enabled(): bool
    {
        return self::provider()->enabled();
    }

    /** @see DisputeProvider::windowDays() */
    public static function windowDays(): int
    {
        return self::provider()->windowDays();
    }

    /** @see DisputeProvider::vendorDays() */
    public static function vendorDays(): int
    {
        return self::provider()->vendorDays();
    }

    /** @see DisputeProvider::eligibility() */
    public static function eligibility(ShopOrder $order, string $customerId): ?string
    {
        return self::provider()->eligibility($order, $customerId);
    }

    /** @see DisputeProvider::refundable() */
    public static function refundable(ShopOrder $order): float
    {
        return self::provider()->refundable($order);
    }

    /**
     * @see DisputeProvider::open()
     * @throws \InvalidArgumentException
     */
    public static function open(ShopOrder $order, string $customerId, string $type, string $reason, ?float $requestedAmount): object
    {
        return self::provider()->open($order, $customerId, $type, $reason, $requestedAmount);
    }

    /** @see DisputeProvider::forOrder() */
    public static function forOrder(string $orderId): ?object
    {
        return self::provider()->forOrder($orderId);
    }

    /** @see DisputeProvider::withdraw() */
    public static function withdraw(object $dispute, string $customerId): bool
    {
        return self::provider()->withdraw($dispute, $customerId);
    }

    /** @see DisputeProvider::vendorAccept() */
    public static function vendorAccept(object $dispute, float $amount, string $note = ''): bool
    {
        return self::provider()->vendorAccept($dispute, $amount, $note);
    }

    /** @see DisputeProvider::vendorReject() */
    public static function vendorReject(object $dispute, string $response): bool
    {
        return self::provider()->vendorReject($dispute, $response);
    }

    /** @see DisputeProvider::escalateOverdue() */
    public static function escalateOverdue(): int
    {
        return self::provider()->escalateOverdue();
    }

    /**
     * @see DisputeProvider::countsForStore()
     * @return array{counted:int, answered:int}
     */
    public static function countsForStore(string $storeId, $since): array
    {
        return self::provider()->countsForStore($storeId, $since);
    }

    /** Human label of a type / status / resolution (i18n). */
    public static function label(string $key): string
    {
        return (string) gp247_language_render('multi_vendor.dispute.'.$key);
    }
}
