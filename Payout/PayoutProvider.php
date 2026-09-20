<?php

namespace App\GP247\Plugins\MultiVendor\Payout;

/**
 * What the free payout run needs from the paid payout features.
 *
 * The payout run itself — grouping finished orders into period rows of the
 * ledger, paying them, editing them — is free. Three things around it are paid
 * and are asked through this contract while the run is being built: where the
 * vendor wants to be paid (payout account snapshot), which orders the new period
 * row covers (so a later refund can be clawed back), and how much of the store's
 * pending adjustments to net into the new row. The paid package registers an
 * implementation under `config('Plugins/MultiVendor.payout.provider')`; without
 * one, NullPayoutProvider records nothing and nets nothing.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-paid-code-location
 * @aidlc-adr multi-vendor_pro-code-relocation
 */
interface PayoutProvider
{
    /**
     * Where the store wants to be paid, as it stands now, for the period row's
     * snapshot columns. Empty strings when nothing is configured.
     *
     * @return array{method: string, masked: string}
     */
    public function accountSnapshot(string $storeId): array;

    /**
     * Remember which orders a new period row paid. Returns how many were linked.
     *
     * @param iterable<\GP247\Shop\Models\ShopOrder> $orders
     */
    public function recordPeriodOrders(string $processId, string $storeId, string $currency, int $vendorShare, iterable $orders): int;

    /**
     * Absorb the store's pending adjustments (same currency) into the new period
     * row and mark them netted. Returns the signed amount to add to the row.
     */
    public function netPending(string $storeId, string $currency, string $intoProcessId): float;
}
