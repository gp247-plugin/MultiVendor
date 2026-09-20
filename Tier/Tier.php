<?php

namespace App\GP247\Plugins\MultiVendor\Tier;

use GP247\Core\Models\AdminStore;

/**
 * Free / Pro boundary of the marketplace plugin (S4-1, decision 2026-09-12).
 *
 * `MultiVendor` (this plugin, public) is the Free edition and carries the whole
 * codebase. `MultiVendorPro` is a thin unlock plugin (requireGp247Extensions:
 * MultiVendor) distributed through the paid channel; its presence (installed +
 * active, i.e. its admin_config registration row) turns every Pro feature on.
 * Licences are verified by the distribution channel, not at runtime — the same
 * stance as MultiStore / MultiStorePro (ADR multi-store_free-pro-split): the Free
 * limits are a segmentation and upsell line, not a technical fence (OSS).
 *
 * WHY one class: every Pro/Free branch in the plugin asks this class, so the
 * matrix (PRFAQ "Ma trận tính năng Free / Pro") lives in exactly one place and
 * flipping a feature between tiers is a one-line change here.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-free-tier-split
 * @aidlc-adr multi-vendor_free-pro-split
 */
final class Tier
{
    /** Registration key of the Pro unlock plugin. */
    public const PRO_KEY = 'MultiVendorPro';

    /** Free edition: at most this many vendor stores (ROOT excluded). */
    public const FREE_VENDOR_QUOTA = 3;

    public const F_PER_VENDOR_COMMISSION = 'per_vendor_commission';
    public const F_QUICK_ORDER = 'quick_order';
    public const F_REPORTS = 'reports';
    public const F_ORDER_FULFILLMENT = 'order_fulfillment';   // presets beyond "shipping"
    public const F_MODERATION_QUEUE = 'moderation_queue';
    public const F_PAYOUT_ACCOUNT = 'payout_account';
    public const F_PAYOUT_STATEMENT = 'payout_statement';
    public const F_MAIL_PENDING_REVIEW = 'mail_pending_review';
    public const F_MAIL_VENDOR_APPROVED = 'mail_vendor_approved';
    public const F_MAIL_PAYOUT_DONE = 'mail_payout_done';
    public const F_VENDOR_PLUGINS = 'vendor_plugins';           // S2-1: vendors configure allowlisted store plugins
    public const F_CLAWBACK = 'clawback';                      // S3-1: automatic payout adjustments after refund/cancel
    public const F_KYC = 'kyc';                                // S3-2: vendor identity profile, verified badge, KYC gates
    public const F_DISPUTE = 'dispute';                        // S3-3: customer disputes / refund requests through the marketplace
    public const F_VENDOR_PLANS = 'vendor_plans';               // S3-4: vendor plans (product cap, plan rate, period fee)
    public const F_VENDOR_REVIEWS = 'vendor_reviews';           // S5-1: vendors answer the reviews of their own store
    public const F_PAYOUT_BATCH = 'payout_batch';               // S5-2: bank instruction file + settle a whole payout batch
    public const F_CUSTOMER_GROUP_PRICING = 'customer_group_pricing'; // S5-4a: per-shop dealer groups and their discount
    public const F_VENDOR_ORDER_CREATE = 'vendor_order_create';   // S5-4b: the shop's staff types an order for a customer

    /** @var string[] Everything a Free marketplace does NOT get (PRFAQ matrix). */
    public const PRO_FEATURES = [
        self::F_PER_VENDOR_COMMISSION,
        self::F_QUICK_ORDER,
        self::F_REPORTS,
        self::F_ORDER_FULFILLMENT,
        self::F_MODERATION_QUEUE,
        self::F_PAYOUT_ACCOUNT,
        self::F_PAYOUT_STATEMENT,
        self::F_MAIL_PENDING_REVIEW,
        self::F_MAIL_VENDOR_APPROVED,
        self::F_MAIL_PAYOUT_DONE,
        self::F_VENDOR_PLUGINS,
        self::F_CLAWBACK,
        self::F_KYC,
        self::F_DISPUTE,
        self::F_VENDOR_PLANS,
        self::F_VENDOR_REVIEWS,
        self::F_PAYOUT_BATCH,
        self::F_CUSTOMER_GROUP_PRICING,
        self::F_VENDOR_ORDER_CREATE,
    ];

    /**
     * Pro edition active? True when the MultiVendorPro unlock plugin is installed
     * and enabled (its registration row value is ON).
     */
    public static function isPro(): bool
    {
        try {
            return (bool) gp247_config_global(self::PRO_KEY, 0);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Whether a feature is available in the current edition. Unknown feature
     * names are treated as Free (always allowed) so a typo never locks a Free
     * capability behind Pro.
     */
    public static function allows(string $feature): bool
    {
        if (!in_array($feature, self::PRO_FEATURES, true)) {
            return true;
        }

        return self::isPro();
    }

    /** Vendor-store quota of the current edition; null = unlimited (Pro). */
    public static function vendorQuota(): ?int
    {
        return self::isPro() ? null : self::FREE_VENDOR_QUOTA;
    }

    /** Vendor stores currently on the marketplace (ROOT excluded). */
    public static function vendorCount(): int
    {
        return (int) AdminStore::where('id', '<>', GP247_STORE_ID_ROOT)->count();
    }

    /**
     * True when the Free quota is full — creating or registering one more vendor
     * store must be refused (server-side, both admin create and self-register).
     */
    public static function quotaReached(): bool
    {
        $quota = self::vendorQuota();

        return $quota !== null && self::vendorCount() >= $quota;
    }

    /** Human label of the current edition for the admin UI. */
    public static function label(): string
    {
        return self::isPro() ? 'Pro' : 'Free';
    }
}
