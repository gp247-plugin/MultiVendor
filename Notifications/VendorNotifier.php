<?php

namespace App\GP247\Plugins\MultiVendor\Notifications;

use App\GP247\Plugins\MultiVendor\Admin\Models\AdminMoneyProcess;
use App\GP247\Plugins\MultiVendor\Models\VendorUser;
use GP247\Core\Models\AdminStore;
use GP247\Shop\Models\ShopOrder;
use App\GP247\Plugins\MultiVendor\Tier\Tier;
use Illuminate\Support\Facades\Route;

/**
 * The single place MultiVendor sends marketplace e-mails from.
 *
 * Four events (S1-2, US-multi-vendor-pro-marketplace-notifications):
 *   - order_created   → every active vendor user of the order's store
 *   - pending_review  → the marketplace (ROOT store) e-mail, for a vendor or a
 *                       product waiting for approval
 *   - vendor_approved → the vendor users of a store the admin just opened
 *   - payout_done     → the vendor users of a store whose payout row became `done`
 *
 * Delivery goes through gp247_mail_send(), so the core toggles apply unchanged:
 * `email_action_mode` off → nothing is sent; `email_action_queue` on → queued.
 * gp247_mail_process_send() already swallows mailer errors on the sync path, so a
 * broken SMTP can never break checkout, approval or payout (NFR-mv-mail-non-blocking).
 *
 * WHY every method is a static that returns bool: the callers are model events,
 * Livewire actions and event listeners — none of them care about the mail; they
 * only need "fire and forget" that cannot throw. The bool ("accepted for delivery,
 * or skipped") exists for the Feature-Tests.
 *
 * WHY tierAllows() is a separate method: the Free/Pro split decided 2026-09-12
 * keeps only `order_created` in Free. The tier packages do not exist yet (S4);
 * this method is the ONE seam S4 wires to the licence entitlement, so the gate is
 * never scattered across views or callers (ADR multi-vendor_notifications-hook-strategy).
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-marketplace-notifications
 * @aidlc-adr multi-vendor_notifications-hook-strategy
 */
final class VendorNotifier
{
    public const ORDER_CREATED = 'order_created';
    public const PENDING_REVIEW = 'pending_review';
    public const VENDOR_APPROVED = 'vendor_approved';
    public const PAYOUT_DONE = 'payout_done';
    public const PAYOUT_CLAWBACK = 'payout_clawback';   // S3-1: a paid order was refunded/canceled → adjustment
    public const DISPUTE = 'dispute';                   // S3-3: customer dispute opened / answered / decided

    /** @var string[] Every notification event, in config-screen order. */
    public const EVENTS = [
        self::ORDER_CREATED,
        self::PENDING_REVIEW,
        self::VENDOR_APPROVED,
        self::PAYOUT_DONE,
        self::PAYOUT_CLAWBACK,
        self::DISPUTE,
    ];

    /** @var string[] Events the Free tier keeps (decision 2026-09-12: one "new order" mail). */
    public const FREE_EVENTS = [self::ORDER_CREATED];

    /** Blade namespace the plugin Provider registers its views under. */
    public const VIEW_NAMESPACE = 'Plugins/MultiVendor';

    /**
     * admin_config key (global store) that switches one event on/off.
     */
    public static function configKey(string $event): string
    {
        return 'MultiVendor_mail_'.$event;
    }

    /**
     * Tier gate — the only place the Free/Pro split for notifications lives.
     * Until the tier packages exist (S4) every event is allowed; S4 replaces the
     * body with the licence entitlement check and keeps FREE_EVENTS always on.
     */
    public static function tierAllows(string $event): bool
    {
        if (in_array($event, self::FREE_EVENTS, true)) {
            return true;
        }

        // S4-1: the three other mails are Pro (PRFAQ matrix) — one seam, Tier decides.
        return Tier::isPro();
    }

    /**
     * Whether an event should be sent at all: tier gate AND the marketplace flag.
     * A missing flag row (site updated before the seed ran) counts as ON — the
     * seed default is 1 and silently losing mail is the worse failure.
     */
    public static function enabled(string $event): bool
    {
        if (!in_array($event, self::EVENTS, true) || !self::tierAllows($event)) {
            return false;
        }

        $flag = gp247_config_global(self::configKey($event), 1);

        return (bool) (int) $flag;
    }

    /**
     * E1 — a marketplace order was created for a vendor store.
     *
     * Skips ROOT-store orders (those are the marketplace's own, already mailed by
     * gp247_order_process_after_success()). Reads `details` from the model, so the
     * caller must invoke this after the order lines exist (Provider uses
     * DB::afterCommit for that).
     */
    public static function orderCreated(ShopOrder $order): bool
    {
        $storeId = (string) ($order->store_id ?? '');
        if ($storeId === '' || $storeId === (string) GP247_STORE_ID_ROOT) {
            return false;
        }
        if (!self::enabled(self::ORDER_CREATED)) {
            return false;
        }

        $to = self::vendorEmails($storeId);
        if ($to === []) {
            return false;
        }

        $lines = [];
        foreach ($order->details as $detail) {
            $lines[] = [
                'sku' => (string) ($detail->sku ?? ''),
                'name' => (string) ($detail->name ?? ''),
                'qty' => (float) ($detail->qty ?? 0),
                'total' => self::money((float) ($detail->total_price ?? 0), (string) ($order->currency ?? '')),
            ];
        }

        $data = [
            'orderId' => (string) $order->id,
            'storeName' => self::storeName($storeId),
            'customerName' => trim((string) ($order->first_name ?? '').' '.(string) ($order->last_name ?? '')),
            'customerEmail' => (string) ($order->email ?? ''),
            'customerPhone' => (string) ($order->phone ?? ''),
            'address' => implode(' ', array_filter([
                $order->city ?? '', $order->district ?? '',
                $order->address1 ?? '', $order->address2 ?? '', $order->address3 ?? '',
            ])),
            'comment' => (string) ($order->comment ?? ''),
            'currency' => (string) ($order->currency ?? ''),
            'lines' => $lines,
            'total' => self::money((float) ($order->total ?? 0), (string) ($order->currency ?? '')),
            'orderUrl' => Route::has('vendor_admin_order.detail')
                ? gp247_route_admin('vendor_admin_order.detail', ['id' => $order->id])
                : '',
        ];

        return self::send(
            'order_created',
            $data,
            $to,
            gp247_language_render('multi_vendor.mail.order_created.subject', ['order_id' => $order->id])
        );
    }

    /**
     * E2a — a vendor registered and its store is waiting for approval.
     * Called from the CreatedVendorUser listener; does nothing when the store is
     * already open (auto-approve on, or admin-created account).
     */
    public static function vendorRegistered($vendor): bool
    {
        $storeId = (string) ($vendor->store_id ?? '');
        if ($storeId === '') {
            return false;
        }
        $store = AdminStore::find($storeId);
        if ($store === null || (int) $store->status !== 0) {
            return false;
        }

        return self::pendingReview('vendor', $storeId, [
            'subject' => (string) ($vendor->email ?? ''),
            'code' => (string) ($store->code ?? ''),
        ]);
    }

    /**
     * E2b — a vendor saved a product while the marketplace does not auto-approve.
     */
    public static function productPending(string $storeId, string $productId, string $productName): bool
    {
        return self::pendingReview('product', $storeId, [
            'subject' => $productName,
            'code' => $productId,
        ]);
    }

    /**
     * E2 — tell the marketplace owner something waits for review.
     *
     * @param string $kind    'vendor' | 'product'
     * @param array  $context subject (email / product name) + code (store code / product id)
     */
    public static function pendingReview(string $kind, string $storeId, array $context): bool
    {
        if (!self::enabled(self::PENDING_REVIEW)) {
            return false;
        }
        $admin = self::adminEmail();
        if ($admin === '') {
            return false;
        }

        $data = [
            'kind' => $kind,
            'storeName' => self::storeName($storeId),
            'subject' => (string) ($context['subject'] ?? ''),
            'code' => (string) ($context['code'] ?? ''),
        ];

        return self::send(
            'pending_review',
            $data,
            [$admin],
            gp247_language_render('multi_vendor.mail.pending_review.subject_'.$kind, ['store' => $data['storeName']])
        );
    }

    /**
     * E3 — the admin opened a vendor store (status 0 → 1).
     */
    public static function vendorApproved(string $storeId): bool
    {
        if (!self::enabled(self::VENDOR_APPROVED)) {
            return false;
        }
        $to = self::vendorEmails($storeId);
        if ($to === []) {
            return false;
        }

        $data = [
            'storeName' => self::storeName($storeId),
            'loginUrl' => Route::has('vendor.login') ? gp247_route_admin('vendor.login') : '',
            'storeUrl' => function_exists('gp247_vendor_get_url') ? (string) gp247_vendor_get_url($storeId) : '',
        ];

        return self::send(
            'vendor_approved',
            $data,
            $to,
            gp247_language_render('multi_vendor.mail.vendor_approved.subject', ['store' => $data['storeName']])
        );
    }

    /**
     * E3b — the marketplace rejected a pending store (S1-4). Same flag as
     * vendor_approved: the flag means "approved / rejected" (decision 2026-09-12).
     */
    public static function vendorRejected(string $storeId, string $reason): bool
    {
        if (!self::enabled(self::VENDOR_APPROVED)) {
            return false;
        }
        $to = self::vendorEmails($storeId);
        if ($to === []) {
            return false;
        }
        $data = [
            'storeName' => self::storeName($storeId),
            'reason' => $reason,
            'loginUrl' => Route::has('vendor.login') ? gp247_route_admin('vendor.login') : '',
        ];

        return self::send(
            'vendor_rejected',
            $data,
            $to,
            gp247_language_render('multi_vendor.mail.vendor_rejected.subject', ['store' => $data['storeName']])
        );
    }

    /**
     * S3-2 — the store's identity profile was approved or rejected (under the
     * vendor_approved flag, like the other review decisions).
     */
    public static function kycReviewed(string $storeId, bool $approved, string $reason = ''): bool
    {
        if (!self::enabled(self::VENDOR_APPROVED)) {
            return false;
        }
        $to = self::vendorEmails($storeId);
        if ($to === []) {
            return false;
        }
        $data = [
            'storeName' => self::storeName($storeId),
            'approved' => $approved,
            'reason' => $reason,
            'kycUrl' => Route::has('vendor_admin_kyc.index') ? gp247_route_admin('vendor_admin_kyc.index') : '',
        ];
        $subjectKey = $approved ? 'multi_vendor.mail.kyc_reviewed.subject_approved' : 'multi_vendor.mail.kyc_reviewed.subject_rejected';

        return self::send('kyc_reviewed', $data, $to, gp247_language_render($subjectKey, ['store' => $data['storeName']]));
    }

    /**
     * E3c — a vendor product was approved or rejected by the marketplace (S1-4).
     * Under the vendor_approved flag (decision 2026-09-12 Q2-A: one "approved /
     * rejected" flag for stores and products).
     */
    public static function productReviewed(string $storeId, string $productLabel, bool $approved, string $reason = ''): bool
    {
        if (!self::enabled(self::VENDOR_APPROVED)) {
            return false;
        }
        $to = self::vendorEmails($storeId);
        if ($to === []) {
            return false;
        }
        $data = [
            'storeName' => self::storeName($storeId),
            'product' => $productLabel,
            'approved' => $approved,
            'reason' => $reason,
            'productsUrl' => Route::has('vendor_admin_product.index') ? gp247_route_admin('vendor_admin_product.index') : '',
        ];
        $subjectKey = $approved ? 'multi_vendor.mail.product_reviewed.subject_approved' : 'multi_vendor.mail.product_reviewed.subject_rejected';

        return self::send(
            'product_reviewed',
            $data,
            $to,
            gp247_language_render($subjectKey, ['product' => $productLabel])
        );
    }

    /**
     * E4 — a payout ledger row was marked `done` by the admin.
     */
    public static function payoutDone(AdminMoneyProcess $row): bool
    {
        if (!self::enabled(self::PAYOUT_DONE)) {
            return false;
        }
        $storeId = (string) ($row->store_id ?? '');
        $to = $storeId !== '' ? self::vendorEmails($storeId) : [];
        if ($to === []) {
            return false;
        }

        $currency = (string) ($row->currency ?? '');
        $data = [
            'storeName' => self::storeName($storeId),
            'dateProcess' => (string) ($row->date_process ?? ''),
            'datePay' => (string) ($row->date_pay ?? ''),
            'orderCount' => (int) ($row->order_count ?? 0),
            'totalSum' => self::money((float) ($row->total_sum ?? 0), $currency),
            'commissionRate' => (int) ($row->commission_rate ?? 0),
            'amount' => self::money((float) ($row->amount ?? 0), $currency),
            'currency' => $currency,
            'comment' => (string) ($row->comment ?? ''),
            'payoutMethod' => (string) ($row->payout_method ?? ''),
            'payoutAccount' => (string) ($row->payout_account ?? ''),
            'payoutReference' => (string) ($row->payout_reference ?? ''),
            'paymentUrl' => Route::has('vendor_admin_payment.index') ? gp247_route_admin('vendor_admin_payment.index') : '',
        ];

        return self::send(
            'payout_done',
            $data,
            $to,
            gp247_language_render('multi_vendor.mail.payout_done.subject', ['store' => $data['storeName']])
        );
    }

    /**
     * Active vendor users of one store — the recipients of every vendor-facing mail.
     *
     * @return string[] unique, non-empty e-mails
     */
    /**
     * S3-1: tell the vendor a payout adjustment (clawback / partial refund / reversal)
     * was booked against their store, to be netted into the next payout.
     *
     * @param AdminMoneyProcess $row   The adjustment ledger row.
     * @param ShopOrder|null    $order The order it stems from (may already be gone).
     */
    public static function payoutClawback(AdminMoneyProcess $row, ?ShopOrder $order = null): bool
    {
        if (!self::enabled(self::PAYOUT_CLAWBACK)) {
            return false;
        }
        $storeId = (string) ($row->store_id ?? '');
        $to = $storeId !== '' ? self::vendorEmails($storeId) : [];
        if ($to === []) {
            return false;
        }
        $currency = (string) ($row->currency ?? '');
        $data = [
            'storeName' => self::storeName($storeId),
            'kindLabel' => (string) gp247_language_render('multi_vendor.clawback.kind_'.(string) $row->kind),
            'orderId' => (string) ($order->id ?? ''),
            'totalSum' => self::money((float) ($row->total_sum ?? 0), $currency),
            'amount' => self::money((float) ($row->amount ?? 0), $currency),
            'dateProcess' => (string) ($row->date_process ?? ''),
            'content' => (string) ($row->content ?? ''),
            'paymentUrl' => Route::has('vendor_admin_payment.index') ? gp247_route_admin('vendor_admin_payment.index') : '',
        ];

        return self::send('payout_clawback', $data, $to, gp247_language_render('multi_vendor.mail.payout_clawback.subject', ['store' => $data['storeName']]));
    }

    /**
     * S3-3 — a customer opened a dispute: tell the vendor (who must answer) and the marketplace.
     */
    public static function disputeOpened($dispute, ShopOrder $order): bool
    {
        if (!self::enabled(self::DISPUTE)) {
            return false;
        }
        $storeId = (string) $dispute->store_id;
        $to = array_filter(array_merge(self::vendorEmails($storeId), [self::adminEmail()]));
        if ($to === []) {
            return false;
        }
        $data = self::disputeData($dispute, $order) + [
            'url' => Route::has('vendor_admin_order.detail') ? gp247_route_admin('vendor_admin_order.detail', ['id' => $order->id]) : '',
        ];

        return self::send('dispute', $data + ['phase' => 'opened'], array_values($to), gp247_language_render('multi_vendor.mail.dispute_opened.subject', ['order' => (string) $order->id]));
    }

    /**
     * S3-3 — the vendor refused; the customer learns the marketplace now decides.
     */
    public static function disputeVendorResponded($dispute, ShopOrder $order): bool
    {
        if (!self::enabled(self::DISPUTE) || trim((string) $order->email) === '') {
            return false;
        }
        $data = self::disputeData($dispute, $order) + [
            'response' => (string) ($dispute->vendor_response ?? ''),
            'url' => self::customerOrderUrl($order),
        ];

        return self::send('dispute', $data + ['phase' => 'vendor_responded'], [(string) $order->email], gp247_language_render('multi_vendor.mail.dispute_vendor_responded.subject', ['order' => (string) $order->id]));
    }

    /**
     * S3-3 — final decision (vendor accepted or marketplace ruled): customer + vendor.
     */
    public static function disputeResolved($dispute, ShopOrder $order): bool
    {
        if (!self::enabled(self::DISPUTE)) {
            return false;
        }
        $currency = (string) ($dispute->currency ?? $order->currency);
        $data = self::disputeData($dispute, $order) + [
            'resolutionLabel' => (string) gp247_language_render('multi_vendor.dispute.resolution_'.(string) $dispute->resolution),
            'amount' => $dispute->resolution_amount ? self::money((float) $dispute->resolution_amount, $currency) : '',
            'note' => (string) ($dispute->resolution_note ?? ''),
            'url' => self::customerOrderUrl($order),
        ];
        $subject = gp247_language_render('multi_vendor.mail.dispute_resolved.subject', ['order' => (string) $order->id]);
        $sent = trim((string) $order->email) !== '' && self::send('dispute', $data + ['phase' => 'resolved'], [(string) $order->email], $subject);
        $vendors = self::vendorEmails((string) $dispute->store_id);
        if ($vendors !== []) {
            $data['url'] = Route::has('vendor_admin_order.detail') ? gp247_route_admin('vendor_admin_order.detail', ['id' => $order->id]) : '';
            $sent = self::send('dispute', $data + ['phase' => 'resolved'], $vendors, $subject) || $sent;
        }

        return $sent;
    }

    /** @return array<string, mixed> */
    private static function disputeData($dispute, ShopOrder $order): array
    {
        $currency = (string) ($dispute->currency ?? $order->currency);

        return [
            'storeName' => self::storeName((string) $dispute->store_id),
            'orderId' => (string) $order->id,
            'typeLabel' => (string) gp247_language_render('multi_vendor.dispute.type_'.(string) $dispute->type),
            'reason' => (string) $dispute->reason,
            'requested' => $dispute->requested_amount ? self::money((float) $dispute->requested_amount, $currency) : '',
            'deadline' => $dispute->deadline_at ? $dispute->deadline_at->format('Y-m-d H:i') : '',
        ];
    }

    private static function customerOrderUrl(ShopOrder $order): string
    {
        try {
            return Route::has('customer.order_detail') ? (string) gp247_route_front('customer.order_detail', ['id' => $order->id]) : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    public static function vendorEmails(string $storeId): array
    {
        $emails = VendorUser::where('store_id', $storeId)
            ->where('status', 1)
            ->pluck('email')
            ->filter(fn ($e) => is_string($e) && trim($e) !== '')
            ->map(fn ($e) => trim($e))
            ->unique()
            ->values()
            ->all();

        return $emails;
    }

    /** The marketplace (ROOT store) contact e-mail. */
    protected static function adminEmail(): string
    {
        return trim((string) gp247_store_info('email', null, GP247_STORE_ID_ROOT));
    }

    protected static function storeName(string $storeId): string
    {
        $store = AdminStore::with('descriptions')->find($storeId);
        if ($store === null) {
            return $storeId;
        }
        $desc = $store->descriptions->first();
        $name = $desc?->title ?? $desc?->name ?? null;

        return trim((string) ($name ?: ($store->code ?? $storeId)));
    }

    protected static function money(float $amount, string $currency): string
    {
        if (function_exists('gp247_currency_render')) {
            try {
                return (string) gp247_currency_render($amount, $currency ?: null, null, false, false);
            } catch (\Throwable $e) {
                // fall through to the plain number below
            }
        }

        return number_format($amount, 2).($currency !== '' ? ' '.$currency : '');
    }

    /**
     * One mail per recipient through gp247_mail_send(). Returns true when at least
     * one recipient was accepted (sent or queued).
     */
    protected static function send(string $view, array $data, array $to, string $subject): bool
    {
        $accepted = false;
        foreach (array_unique($to) as $email) {
            $ok = gp247_mail_send(
                self::VIEW_NAMESPACE.'::email.'.$view,
                $data,
                ['to' => $email, 'subject' => $subject],
                []
            );
            $accepted = $accepted || (bool) $ok;
        }

        return $accepted;
    }
}
