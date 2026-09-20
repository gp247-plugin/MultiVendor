<?php

namespace App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor;

use App\GP247\Plugins\MultiVendor\Admin\Models\AdminMoneyProcess;
use GP247\Shop\Admin\Models\AdminOrder;

/**
 * Vendor payment list — read-only payout ledger for the signed-in vendor's own
 * store, now inheriting the core DataTableComponent through VendorDataTable (Pha 2
 * L1 migration of VendorPaymentController::index). Shows the per-currency payout
 * summary tiles (life-time / paid-out / remaining) above the paginated
 * money-process rows.
 *
 * There is no create/edit/delete/search/sort UI: the vendor only reads what the
 * marketplace has settled, so the screen is a single full-width list. Store
 * scoping lives in constrain(); the summary tiles are passed via viewData().
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-vendor-admin-livewire
 * @aidlc-adr multi-vendor_admin-livewire-migration
 */
class VendorPaymentList extends VendorDataTable
{
    /** Full-page title language key (resolved by the base render()). */
    protected ?string $titleKey = 'multi_vendor.vendor_payment';

    /** Vendor shell header icon (matches the legacy screen). */
    protected string $icon = 'fa fa-indent';

    /**
     * A fresh money-process model; the base builds the (store-scoped, newest-first)
     * listing query from it.
     *
     * @return AdminMoneyProcess
     */
    protected function query()
    {
        return new AdminMoneyProcess();
    }

    /**
     * No sortable columns — the ledger is read-only with no clickable headers, so
     * the sort whitelist is intentionally empty.
     *
     * @return array<string, string>
     */
    protected function columns(): array
    {
        return [];
    }

    /**
     * Default ordering — newest processing date first, matching the old
     * VendorPaymentController::index() (orderBy date_process desc).
     *
     * @return array{0: string, 1: string}
     */
    protected function defaultSort(): array
    {
        return ['date_process', 'desc'];
    }

    /**
     * Scope the ledger to the vendor's own store (same guard the controller used).
     *
     * @param mixed $query
     * @return void
     */
    protected function constrain($query): void
    {
        $query->where('store_id', $this->vendorStoreId());
    }

    /**
     * The existing vendor blade drives the summary + table.
     *
     * @return string
     */
    protected function listView(): string
    {
        return 'Plugins/MultiVendor::Admin.screen.vendor.livewire.payment_list';
    }

    /**
     * Pass the per-currency payout summary to the blade via the base viewData()
     * hook (the blade reads it as $dataAmount).
     *
     * @return array<string, mixed>
     */
    protected function viewData(): array
    {
        // S1-1: the rate this store is currently charged (override or marketplace).
        $rate = \App\GP247\Plugins\MultiVendor\Commission\CommissionPolicy::rateFor((string) $this->vendorStoreId());

        return [
            'dataAmount' => $this->summaryByCurrency(),
            // S1-5: where to pay me — a paid nested component; null shows the upgrade hint.
            'accountForm' => \App\GP247\Plugins\MultiVendor\Payout\Payout::accountForm(),
            'canStatement' => \App\GP247\Plugins\MultiVendor\Tier\Tier::allows(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_PAYOUT_STATEMENT),
            'exportUrl' => \Illuminate\Support\Facades\Route::has('vendor_admin_payment.export') ? gp247_route_admin('vendor_admin_payment.export') : '',
            'commissionRate' => $rate,
            'vendorShare' => \App\GP247\Plugins\MultiVendor\Commission\CommissionPolicy::vendorShare($rate),
        ];
    }

    /**
     * Per-currency payout summary keyed by currency code. Mirrors the array the
     * controller assembled: for every active currency, the life-time order sum,
     * the amount already paid out, and the amount still remaining. Preserved
     * verbatim — the same three helper/query sources, no reformatting here.
     *
     * @return array<string, array<string, mixed>>
     */
    public function summaryByCurrency(): array
    {
        $storeId = $this->vendorStoreId();

        $dataAmount = [];
        foreach (gp247_currency_all_active() as $code => $name) {
            $dataAmount[$code] = [];
        }

        if ($sumAmount = AdminOrder::getSumAmountOrder($storeId)) {
            foreach ($sumAmount as $row) {
                $dataAmount[$row['currency']]['sumAmount'] = $row['total_sum'];
            }
        }
        if ($sumAmountDone = gp247_cal_amount_payment_done($storeId)) {
            foreach ($sumAmountDone as $row) {
                $dataAmount[$row['currency']]['sumAmountDone'] = $row['amount'];
            }
        }
        if ($sumAmountRemaining = gp247_cal_amount_payment_remaining($storeId)) {
            foreach ($sumAmountRemaining as $row) {
                $dataAmount[$row['currency']]['sumAmountRemaining'] = $row['amount'];
            }
        }

        return $dataAmount;
    }
}
