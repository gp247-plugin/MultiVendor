<?php

namespace App\GP247\Plugins\MultiVendor\Dispute;

use GP247\Shop\Models\ShopOrder;

/**
 * What the marketplace needs from the dispute desk without owning it.
 *
 * The free plugin has three places where a dispute surfaces — the customer's
 * order page (storefront hook), the vendor's order screen (answer panel) and
 * the shop's trust strip (complaints answered) — and one settings screen with
 * the two window lengths. All of them ask through this contract. The desk
 * itself — table, rules, refund booking, escalation, the marketplace's
 * decision screen — ships with the paid package, which registers an
 * implementation under `config('Plugins/MultiVendor.dispute.provider')`.
 * Without one, NullDisputeProvider answers "no dispute desk here".
 *
 * Dispute rows are passed as `object` on purpose: the free plugin reads their
 * attributes (status, store_id, amounts) but never names the paid model.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-paid-code-location
 * @aidlc-adr multi-vendor_pro-code-relocation
 */
interface DisputeProvider
{
    /** Whether this marketplace runs a dispute desk at all. */
    public function enabled(): bool;

    /** Days after the order finished during which a customer may still open a dispute. */
    public function windowDays(): int;

    /** Days the vendor has to answer before the dispute escalates to the marketplace. */
    public function vendorDays(): int;

    /**
     * Why this customer may NOT open a dispute on this order — a language-key
     * suffix (`disabled`, `not_owner`, `not_vendor`, `closed`, `window`, `live`
     * …) — or null when they may.
     */
    public function eligibility(ShopOrder $order, string $customerId): ?string;

    /** The amount still refundable on the order. */
    public function refundable(ShopOrder $order): float;

    /**
     * Open a dispute for the customer.
     *
     * @throws \InvalidArgumentException With the eligibility key when the order does not qualify.
     */
    public function open(ShopOrder $order, string $customerId, string $type, string $reason, ?float $requestedAmount): object;

    /** The order's dispute row, or null when none. */
    public function forOrder(string $orderId): ?object;

    /** The customer takes their still-open dispute back. */
    public function withdraw(object $dispute, string $customerId): bool;

    /** The vendor accepts and refunds $amount right away. */
    public function vendorAccept(object $dispute, float $amount, string $note = ''): bool;

    /** The vendor refuses with a reason; the dispute escalates to the marketplace. */
    public function vendorReject(object $dispute, string $response): bool;

    /** Escalate every open dispute whose vendor deadline passed. Returns how many. */
    public function escalateOverdue(): int;

    /**
     * Complaints of a shop since a date that count against it, and how many
     * of those the vendor answered before the marketplace stepped in.
     *
     * @param  \DateTimeInterface $since
     * @return array{counted:int, answered:int}
     */
    public function countsForStore(string $storeId, $since): array;
}
