<?php

namespace App\GP247\Plugins\MultiVendor\Tier;

use Illuminate\Support\Facades\Route;

/**
 * What the Pro edition adds, in words a marketplace owner can read.
 *
 * `Tier` says WHICH features are Pro; this class says what each one is called,
 * what it does in one line, who cares about it (marketplace owner or shop), and
 * — for features that are a screen — which real route opens it and which slug
 * the always-present gateway (`/pro/{feature}`) uses for it. The Free edition
 * shows this catalogue wherever a Pro feature would otherwise be missing, so a
 * Free user always learns the feature exists instead of meeting a dead link.
 *
 * Adding a Pro screen = one row in SCREENS + one title/description pair in
 * AppConfig::seedProFunnelLanguage(). Adding a Pro feature without a screen =
 * one row in FEATURES + the same language pair.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-upgrade-funnel
 * @aidlc-adr multi-vendor_pro-upgrade-funnel
 */
final class ProFeatureCatalogue
{
    /** Who a feature is for. Root = the marketplace owner (the buyer); vendor = a shop on the marketplace. */
    public const ROOT = 'root';
    public const VENDOR = 'vendor';

    /** Gateway route name per audience (registered unconditionally in Route.php). */
    public const GATEWAY_ROUTES = [
        self::ROOT => 'admin_MultiVendor.pro',
        self::VENDOR => 'vendor_admin.pro',
    ];

    /**
     * Pro screens: gateway slug → flag, audience, real route, icon, title key.
     * The root titles are the marketplace sidebar labels (menu.*); the vendor
     * titles reuse the screens' own headings.
     *
     * @var array<string, array{flag: string, audience: string, route: string, icon: string, title: string}>
     */
    public const SCREENS = [
        'report' => ['flag' => Tier::F_REPORTS, 'audience' => self::ROOT, 'route' => 'admin_MultiVendorReport.index', 'icon' => 'fas fa-file-download', 'title' => 'multi_vendor.pro.menu.report'],
        'commission' => ['flag' => Tier::F_REPORTS, 'audience' => self::ROOT, 'route' => 'admin_MultiVendorReport.commission', 'icon' => 'fas fa-percent', 'title' => 'multi_vendor.pro.menu.commission'],
        'review' => ['flag' => Tier::F_MODERATION_QUEUE, 'audience' => self::ROOT, 'route' => 'admin_MultiVendorReview.index', 'icon' => 'fas fa-clipboard-check', 'title' => 'multi_vendor.pro.menu.review'],
        'dispute' => ['flag' => Tier::F_DISPUTE, 'audience' => self::ROOT, 'route' => 'admin_MultiVendorDispute.index', 'icon' => 'fas fa-gavel', 'title' => 'multi_vendor.pro.menu.dispute'],
        'plan' => ['flag' => Tier::F_VENDOR_PLANS, 'audience' => self::ROOT, 'route' => 'admin_MultiVendorPlan.index', 'icon' => 'fas fa-layer-group', 'title' => 'multi_vendor.pro.menu.plan'],

        'kyc' => ['flag' => Tier::F_KYC, 'audience' => self::VENDOR, 'route' => 'vendor_admin_kyc.index', 'icon' => 'fas fa-id-card', 'title' => 'multi_vendor.kyc.title'],
        'my-plan' => ['flag' => Tier::F_VENDOR_PLANS, 'audience' => self::VENDOR, 'route' => 'vendor_admin_plan.index', 'icon' => 'fas fa-layer-group', 'title' => 'multi_vendor.plan_self.title'],
        'order-create' => ['flag' => Tier::F_VENDOR_ORDER_CREATE, 'audience' => self::VENDOR, 'route' => 'vendor_admin_order.create', 'icon' => 'fas fa-file-invoice', 'title' => 'multi_vendor.order_create.title'],
        'customer-groups' => ['flag' => Tier::F_CUSTOMER_GROUP_PRICING, 'audience' => self::VENDOR, 'route' => 'vendor_admin_customer_group.index', 'icon' => 'fas fa-user-tag', 'title' => 'multi_vendor.groups.title'],
        'reviews' => ['flag' => Tier::F_VENDOR_REVIEWS, 'audience' => self::VENDOR, 'route' => 'vendor_admin_review.index', 'icon' => 'far fa-comments', 'title' => 'multi_vendor.reviews.title'],
        'plugins' => ['flag' => Tier::F_VENDOR_PLUGINS, 'audience' => self::VENDOR, 'route' => 'vendor_admin_plugin.index', 'icon' => 'fas fa-plug', 'title' => 'multi_vendor.vendor_plugins.title'],
    ];

    /**
     * Every Pro flag → the audiences it matters to. Features without a screen
     * (mails, clawback, fulfilment scope, quick order) live only here, so the
     * gateway page can still list them.
     *
     * @var array<string, string[]>
     */
    public const FEATURES = [
        Tier::F_PER_VENDOR_COMMISSION => [self::ROOT],
        Tier::F_QUICK_ORDER => [self::ROOT, self::VENDOR],
        Tier::F_REPORTS => [self::ROOT],
        Tier::F_ORDER_FULFILLMENT => [self::ROOT, self::VENDOR],
        Tier::F_MODERATION_QUEUE => [self::ROOT],
        Tier::F_PAYOUT_ACCOUNT => [self::ROOT, self::VENDOR],
        Tier::F_PAYOUT_STATEMENT => [self::ROOT, self::VENDOR],
        Tier::F_MAIL_PENDING_REVIEW => [self::ROOT],
        Tier::F_MAIL_VENDOR_APPROVED => [self::ROOT],
        Tier::F_MAIL_PAYOUT_DONE => [self::ROOT, self::VENDOR],
        Tier::F_VENDOR_PLUGINS => [self::ROOT, self::VENDOR],
        Tier::F_CLAWBACK => [self::ROOT],
        Tier::F_KYC => [self::ROOT, self::VENDOR],
        Tier::F_DISPUTE => [self::ROOT],
        Tier::F_VENDOR_PLANS => [self::ROOT, self::VENDOR],
        Tier::F_VENDOR_REVIEWS => [self::ROOT, self::VENDOR],
        Tier::F_PAYOUT_BATCH => [self::ROOT],
        Tier::F_CUSTOMER_GROUP_PRICING => [self::ROOT, self::VENDOR],
        Tier::F_VENDOR_ORDER_CREATE => [self::ROOT, self::VENDOR],
    ];

    /**
     * One Font Awesome 5 icon per Pro flag, for the gateway's feature cards.
     * Presentation only: a flag without an icon falls back to a star.
     *
     * @var array<string, string>
     */
    public const ICONS = [
        Tier::F_PER_VENDOR_COMMISSION => 'fas fa-percent',
        Tier::F_QUICK_ORDER => 'fas fa-bolt',
        Tier::F_REPORTS => 'fas fa-chart-line',
        Tier::F_ORDER_FULFILLMENT => 'fas fa-truck',
        Tier::F_MODERATION_QUEUE => 'fas fa-clipboard-check',
        Tier::F_PAYOUT_ACCOUNT => 'fas fa-wallet',
        Tier::F_PAYOUT_STATEMENT => 'fas fa-file-invoice-dollar',
        Tier::F_MAIL_PENDING_REVIEW => 'fas fa-envelope-open-text',
        Tier::F_MAIL_VENDOR_APPROVED => 'fas fa-envelope',
        Tier::F_MAIL_PAYOUT_DONE => 'fas fa-paper-plane',
        Tier::F_VENDOR_PLUGINS => 'fas fa-plug',
        Tier::F_CLAWBACK => 'fas fa-undo',
        Tier::F_KYC => 'fas fa-id-card',
        Tier::F_DISPUTE => 'fas fa-gavel',
        Tier::F_VENDOR_PLANS => 'fas fa-layer-group',
        Tier::F_VENDOR_REVIEWS => 'fas fa-comments',
        Tier::F_PAYOUT_BATCH => 'fas fa-money-check-alt',
        Tier::F_CUSTOMER_GROUP_PRICING => 'fas fa-user-tag',
        Tier::F_VENDOR_ORDER_CREATE => 'fas fa-file-invoice',
    ];

    /** Language key of a feature's name. */
    public static function titleKey(string $flag): string
    {
        return 'multi_vendor.pro.f.'.$flag;
    }

    /** Language key of a feature's one-line description. */
    public static function descKey(string $flag): string
    {
        return 'multi_vendor.pro.f.'.$flag.'_desc';
    }

    /**
     * A screen by gateway slug, or null for an unknown slug.
     *
     * @return array{flag: string, audience: string, route: string, icon: string, title: string}|null
     */
    public static function screen(string $slug): ?array
    {
        return self::SCREENS[$slug] ?? null;
    }

    /**
     * True when the real screen can be opened right now: the edition allows the
     * feature AND its route exists. Tier is the truth; Route::has is the safety
     * net (routes are registered with the edition state at boot).
     */
    public static function isOpen(array $screen): bool
    {
        return Tier::allows($screen['flag']) && Route::has($screen['route']);
    }

    /** Gateway URL for a slug and audience. */
    public static function gatewayUrl(string $audience, string $slug): string
    {
        return gp247_route_admin(self::GATEWAY_ROUTES[$audience], ['feature' => $slug]);
    }

    /**
     * The gateway slug of a real route (sidebar helper), or null.
     */
    public static function slugFor(string $route): ?string
    {
        foreach (self::SCREENS as $slug => $screen) {
            if ($screen['route'] === $route) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * Catalogue rows for one audience, ready for the upsell list.
     *
     * @return array<int, array{flag: string, title: string, desc: string, icon: string, available: bool}>
     */
    public static function forAudience(string $audience): array
    {
        $rows = [];
        foreach (self::FEATURES as $flag => $audiences) {
            if (!in_array($audience, $audiences, true)) {
                continue;
            }
            $rows[] = [
                'flag' => $flag,
                'title' => gp247_language_render(self::titleKey($flag)),
                'desc' => gp247_language_render(self::descKey($flag)),
                'icon' => self::ICONS[$flag] ?? 'fas fa-star',
                'available' => Tier::allows($flag),
            ];
        }

        return $rows;
    }
}
