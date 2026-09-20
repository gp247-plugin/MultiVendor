<?php

namespace App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor;

use App\GP247\Plugins\MultiVendor\Admin\Models\AdminVendorOrder;
use GP247\Shop\Models\ShopOrderStatus;
use Livewire\Attributes\Url;

/**
 * Vendor order list — read-only list + filters, now inheriting the core
 * DataTableComponent through VendorDataTable (Pha 2 L1 "inherit core layout"
 * migration of VendorOrderController::index). Every row is store-scoped to the
 * signed-in vendor; each row links to the existing order-detail screen
 * (vendor_admin_order.detail), which stays a Livewire screen out of scope here.
 *
 * The old brownfield filters are preserved: keyword (base), order status and a
 * created_at date range (extra props applied in constrain()), plus column-header
 * sorting driven by the base setSort() action. There is no delete on this screen
 * (the controller had none).
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-vendor-admin-livewire
 * @aidlc-adr multi-vendor_admin-livewire-migration
 */
class VendorOrderList extends VendorDataTable
{
    /** Full-page title language key (resolved by the base render()). */
    protected ?string $titleKey = 'admin.order.list';

    /** Vendor shell header icon (matches the legacy screen). */
    protected string $icon = 'fa fa-indent';

    /**
     * Free-text search over order id / email / customer name. Redeclared with
     * #[Url] to keep the filter shareable in the URL as the legacy screen did
     * (the core base keeps $keyword un-synced).
     *
     * @var string
     */
    #[Url]
    public string $keyword = '';

    /** Filter by order status id; empty = all statuses. */
    #[Url]
    public string $order_status = '';

    /** Inclusive lower bound on created_at (yyyy-mm-dd); empty = none. */
    #[Url]
    public string $from_to = '';

    /** Inclusive upper bound on created_at (yyyy-mm-dd); empty = none. */
    #[Url]
    public string $end_to = '';

    /**
     * Order-status id => label map (drives the status filter and the row badge).
     *
     * @return array<int|string, string>
     */
    public function statusOrder(): array
    {
        return ShopOrderStatus::getIdAll();
    }

    /**
     * A fresh order model. The base builds the listing query from it; the store
     * scope and extra filters are applied in constrain().
     *
     * @return AdminVendorOrder
     */
    protected function query()
    {
        return new AdminVendorOrder();
    }

    /**
     * Keyword columns OR'd together — same set the old getOrderListAdmin() used.
     *
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['id', 'email', 'first_name', 'last_name'];
    }

    /**
     * Sortable columns (field => label); doubles as the sort whitelist. Only the
     * columns that have a visible header are exposed to setSort().
     *
     * @return array<string, string>
     */
    protected function columns(): array
    {
        return [
            'email'      => gp247_language_render('order.email'),
            'created_at' => gp247_language_render('admin.created_at'),
        ];
    }

    /**
     * Default ordering when no sort column is active — newest first, matching the
     * old getOrderListAdmin() fallback (created_at desc).
     *
     * @return array{0: string, 1: string}
     */
    protected function defaultSort(): array
    {
        return ['created_at', 'desc'];
    }

    /**
     * Store scope + extra filters, applied to BOTH the listing and any delete
     * (there is no delete here, but constrain() is the single seam). Reproduces
     * getOrderListAdmin()'s store/status/date guards verbatim.
     *
     * @param mixed $query
     * @return void
     */
    protected function constrain($query): void
    {
        // WHY guarded by truthiness: mirrors the old `if ($storeId)` — the route's
        // checkStoreExist middleware guarantees a store, so this always scopes.
        if ($storeId = $this->vendorStoreId()) {
            $query->where('store_id', $storeId);
        }

        if ($this->order_status !== '') {
            $query->where('status', gp247_clean($this->order_status));
        }
        if ($this->from_to !== '') {
            $query->where('created_at', '>=', gp247_clean($this->from_to));
        }
        if ($this->end_to !== '') {
            $query->where('created_at', '<=', gp247_clean($this->end_to));
        }
    }

    /**
     * The existing vendor blade drives the table (adapted to $rows + setSort()).
     *
     * @return string
     */
    protected function listView(): string
    {
        return 'Plugins/MultiVendor::Admin.screen.vendor.livewire.order_list';
    }

    /**
     * Extra view data: the status map (filter + badge) and the status style map
     * (Tailwind tint per status), both consumed by the blade unchanged.
     *
     * @return array<string, mixed>
     */
    protected function viewData(): array
    {
        return [
            'statusOrder' => $this->statusOrder(),
            'styleMap'    => AdminVendorOrder::$mapStyleStatus,
        ];
    }

    /** Reset paging when the status filter changes. */
    public function updatedOrderStatus(): void
    {
        $this->resetPage();
    }

    /** Reset paging when the from-date changes. */
    public function updatedFromTo(): void
    {
        $this->resetPage();
    }

    /** Reset paging when the to-date changes. */
    public function updatedEndTo(): void
    {
        $this->resetPage();
    }
}
