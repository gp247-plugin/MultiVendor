<?php

namespace App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor;

use App\GP247\Plugins\MultiVendor\Kyc\Kyc;
use App\GP247\Plugins\MultiVendor\Plans\Plan;
use GP247\Shop\Models\ShopCustomer;
use GP247\Shop\Models\ShopProduct;
use Illuminate\Contracts\View\View;

/**
 * Vendor dashboard — read-only overview for the signed-in vendor's own store
 * (Pha 2 Livewire migration of DashboardVendorController::index). Renders the
 * stat widgets (order/product counts) plus the two ApexCharts (30-day order
 * series, 12-month amount series). No form/actions: the screen only reads
 * aggregates the marketplace computed, so it is a single full-page component.
 *
 * The index() queries are copied verbatim (they were recently fixed —
 * ShopProduct::where('store_id') replaced the retired product-store pivot); the
 * only change is sourcing the store id once via vendorStoreId(), which returns
 * the same session('adminStoreId') the controller read on every line.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-vendor-admin-livewire
 * @aidlc-adr multi-vendor_admin-livewire-migration
 */
class VendorDashboard extends VendorAdminComponent
{
    /**
     * Compute the same $data DashboardVendorController::index() did (queries
     * copied verbatim, store-scoped to the vendor's own store) and render it
     * into the vendor shell.
     *
     * @return View
     */
    public function render(): View
    {
        // vendorStoreId() === session('adminStoreId'); resolved once so every
        // verbatim query below is scoped exactly as the controller scoped it.
        $storeId = $this->vendorStoreId();

        $data = [];
        $data['totalOrder']     = gp247_vendor_count_order($storeId);
        // WHY ShopProduct (not the retired ShopProductStore): the 1-1 store
        // ownership refactor dropped the product-store pivot; a product now
        // carries store_id directly.
        $data['totalProduct']   = ShopProduct::where('store_id', $storeId)->count();
        $data['topCustomer']    = ShopCustomer::where('store_id', $storeId)
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();
        $data['totalCustomer']  = ShopCustomer::where('store_id', $storeId)->count();

        //Order in 30 days
        $totalsInMonth = gp247_vendor_total_order_in_month($storeId)->keyBy('md')->toArray();
        $rangDays = new \DatePeriod(
            new \DateTime('-1 month'),
            new \DateInterval('P1D'),
            new \DateTime('+1 day')
        );
        $orderInMonth  = [];
        $amountInMonth  = [];
        foreach ($rangDays as $i => $day) {
            $date = $day->format('m-d');
            $orderInMonth[$date] = $totalsInMonth[$date]['total_order'] ?? '';
            $amountInMonth[$date] = ($totalsInMonth[$date]['total_amount'] ?? 0);
        }
        $data['orderInMonth'] = $orderInMonth;
        $data['amountInMonth'] = $amountInMonth;
        //End order in 30 days

        //Order in 12 months
        $totalsMonth = gp247_vendor_total_order_in_year($storeId)
            ->pluck('total_amount', 'ym')->toArray();
        $dataInYear = [];
        for ($i = 12; $i >= 0; $i--) {
            $date = date("Y-m", strtotime(date('Y-m-01') . " -$i months"));
            $dataInYear[$date] = $totalsMonth[$date] ?? 0;
        }
        $data['dataInYear'] = $dataInYear;
        //End order in 12 months

        // S3-2: nudge the vendor while the marketplace requires KYC and the store is not verified.
        $data['kycNotice'] = Kyc::blocks((string) $storeId) ? (Kyc::statusOf((string) $storeId) ?? 'none') : '';
        // S3-4: the vendor's plan (cap, period, fee) — null on Free / no plan.
        $data['planInfo'] = Plan::summary((string) $storeId);

        return $this->renderInShell('Plugins/MultiVendor::Admin.screen.vendor.livewire.dashboard', $data);
    }

    /** @return string */
    protected function pageTitle(): string
    {
        return gp247_language_render('admin.dashboard');
    }

    /** @return string */
    protected function pageIcon(): string
    {
        return 'fa fa-tachometer-alt';
    }
}
