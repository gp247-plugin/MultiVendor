<?php

namespace App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor;

use GP247\Core\AdminShell\Infrastructure\ResourcePanel;
use Illuminate\Contracts\View\View;

/**
 * Vendor-side two-panel CRUD base — inherits the core ResourcePanel wholesale
 * (list/search/sort/paginate + create/edit/delete + validation + keep-state) and
 * re-points only the three seams that differ for the vendor area (Pha 2 L1
 * "inherit core layout" migration):
 *
 *   1. Authorization — the vendor area authenticates against the `vendor` guard
 *      and is gated by the route middleware (vendor + checkVendorActive +
 *      checkStoreExist), NOT the admin RBAC Layer-2 the core base enforces, so
 *      both authorize hooks are neutralised here.
 *   2. Layout — ResourcePanel::render() hardcodes the admin shell; this renders
 *      the same panel view into the plugin's vendor shell instead.
 *   3. Store — a vendor only ever works inside its own store; concrete screens
 *      scope baseQuery()/persist() with vendorStoreId() (session `adminStoreId`),
 *      and the core store-picker chrome stays off (storeScoped() default null).
 *
 * A concrete vendor CRUD screen extends this and implements the same 11-method
 * ResourcePanel contract as a core screen, so the layout and behaviour match the
 * core admin screens for free (ADR-007, rule ui-tailadmin P3).
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-vendor-admin-livewire
 * @aidlc-adr multi-vendor_admin-livewire-migration
 */
abstract class VendorResourcePanel extends ResourcePanel
{
    /** The plugin's vendor admin shell (replaces the core admin layout). */
    protected const VENDOR_LAYOUT = 'Plugins/MultiVendor::Admin.layout';

    /**
     * No admin RBAC on read — the route's vendor middleware already gated access.
     *
     * @return void
     */
    protected function authorizeView(): void
    {
    }

    /**
     * No admin RBAC on mutating actions — same route-middleware gate applies, and
     * every write is store-scoped to the signed-in vendor.
     *
     * @param string $method
     * @return void
     */
    protected function authorizeAction(string $method): void
    {
    }

    /**
     * The store the signed-in vendor owns (session `adminStoreId`, set by the
     * AdminStoreId middleware at login). Concrete screens scope every query and
     * write to it.
     *
     * @return string|null
     */
    protected function vendorStoreId(): ?string
    {
        return session('adminStoreId');
    }

    /**
     * Optional Font Awesome icon for the vendor shell header.
     *
     * @return string
     */
    protected function pageIcon(): string
    {
        return '';
    }

    /**
     * Render the per-resource panel view into the vendor shell (the core base
     * hardcodes the admin layout; only the layout differs).
     *
     * @return View
     */
    public function render(): View
    {
        return view($this->panelView(), [
            'rows' => $this->rows(),
        ])->layout(self::VENDOR_LAYOUT, [
            'title' => $this->pageTitle(),
            'icon' => $this->pageIcon(),
        ]);
    }
}
