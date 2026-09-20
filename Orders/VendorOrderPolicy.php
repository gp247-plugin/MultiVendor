<?php

namespace App\GP247\Plugins\MultiVendor\Orders;

use App\GP247\Plugins\MultiVendor\Tier\Tier;
use GP247\Shop\Models\ShopOrder;
use GP247\Shop\Models\ShopOrderStatus;

/**
 * What a vendor may do to an order of their own store (S1-3).
 *
 * The marketplace picks ONE preset (`MultiVendor_vendor_order_scope`):
 *   shipping  — only the shipping status (the 1.0 behaviour)
 *   confirm   — + confirm the order: New/Hold → Processing        (default)
 *   complete  — + finish it: Processing → Done (Done stamps finish_date, i.e. the
 *               order enters the next payout period — that is why it is opt-in)
 *
 * Cancel / Refund / Failed and re-opening a finalized order are NEVER vendor
 * actions: money moves there, so the marketplace (root admin) owns them. A
 * finalized order (ShopOrder::isLocked) gets no transition at all.
 *
 * Every status write goes through ShopOrder::changeStatus() — the core seam that
 * restocks, writes history, fires events and stamps finish_date — never through a
 * plain column update (guard test).
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-vendor-order-fulfillment
 * @aidlc-adr multi-vendor_vendor-order-fulfillment-seam
 */
final class VendorOrderPolicy
{
    public const CONFIG_KEY = 'MultiVendor_vendor_order_scope';

    public const SCOPE_SHIPPING = 'shipping';
    public const SCOPE_CONFIRM = 'confirm';
    public const SCOPE_COMPLETE = 'complete';

    /** @var string[] Presets in escalating order. */
    public const SCOPES = [self::SCOPE_SHIPPING, self::SCOPE_CONFIRM, self::SCOPE_COMPLETE];

    /** Decision 2026-09-12 (Q2-A): vendors confirm and ship; Done stays with the marketplace. */
    public const DEFAULT_SCOPE = self::SCOPE_CONFIRM;

    /** @var int[] Statuses a vendor can never set — money moves there. */
    public const NEVER = [ShopOrderStatus::CANCELED, ShopOrderStatus::FAILED, ShopOrderStatus::REFUNDED];

    /**
     * Active preset; an unknown/missing config value falls back to the default.
     */
    public static function scope(): string
    {
        // S4-1: vendor order actions beyond the shipping status are Pro.
        if (!Tier::allows(Tier::F_ORDER_FULFILLMENT)) {
            return self::SCOPE_SHIPPING;
        }
        $value = (string) gp247_config_global(self::CONFIG_KEY, self::DEFAULT_SCOPE);

        return in_array($value, self::SCOPES, true) ? $value : self::DEFAULT_SCOPE;
    }

    /**
     * Order statuses a vendor may move an order to, from its current status.
     *
     * @return int[] empty when nothing is allowed (locked order, shipping-only preset…)
     */
    public static function allowedTransitions(int $from, ?string $scope = null): array
    {
        $scope = $scope ?? self::scope();
        if (in_array($from, [ShopOrderStatus::DONE, ShopOrderStatus::REFUNDED, ShopOrderStatus::CANCELED], true)) {
            return [];
        }
        if ($scope === self::SCOPE_SHIPPING) {
            return [];
        }

        $allowed = [];
        if (in_array($from, [ShopOrderStatus::NEW, ShopOrderStatus::HOLD], true)) {
            $allowed[] = ShopOrderStatus::PROCESSING;
        }
        if ($scope === self::SCOPE_COMPLETE && $from === ShopOrderStatus::PROCESSING) {
            $allowed[] = ShopOrderStatus::DONE;
        }

        return array_values(array_diff($allowed, self::NEVER));
    }

    public static function canTransition(ShopOrder $order, int $to, ?string $scope = null): bool
    {
        return in_array($to, self::allowedTransitions((int) $order->status, $scope), true);
    }

    /**
     * Shipping status and shipment details stay editable while the order is live
     * or delivered; a cancelled / refunded / failed order is out of the vendor's hands.
     */
    public static function canEditShipping(ShopOrder $order): bool
    {
        return !in_array((int) $order->status, self::NEVER, true);
    }
}
