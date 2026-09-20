<?php

namespace App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor;

use GP247\Core\AdminShell\Infrastructure\DataTableComponent;
use Illuminate\Contracts\View\View;

/**
 * Vendor-side list/table base — inherits the core DataTableComponent (search /
 * sort / paginate / row actions) and re-points only the vendor seams (Pha 2 L1
 * "inherit core layout" migration):
 *
 *   1. Layout — the core base exposes the shell as the `$adminLayout` property, so
 *      the vendor shell is set here without overriding render().
 *   2. Authorization — the vendor area is gated by the route middleware (vendor +
 *      checkVendorActive + checkStoreExist), not admin RBAC, so both authorize
 *      hooks are neutralised.
 *   3. Store — concrete screens scope query()/deletes to vendorStoreId()
 *      (session `adminStoreId`).
 *
 * A concrete vendor list screen extends this and implements DataTableComponent's
 * `query()` + `columns()` contract, so the table matches the core admin lists.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-vendor-admin-livewire
 * @aidlc-adr multi-vendor_admin-livewire-migration
 */
abstract class VendorDataTable extends DataTableComponent
{
    /** Render into the plugin's vendor shell instead of the admin shell. */
    protected string $adminLayout = 'Plugins/MultiVendor::Admin.layout';

    /**
     * Optional Font Awesome icon class shown in the vendor shell header, next to
     * the title. Concrete screens set it (the legacy screens used "fa fa-indent").
     *
     * @var string
     */
    protected string $icon = '';

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
     * The store the signed-in vendor owns (session `adminStoreId`). Concrete
     * screens scope query() and deletes to it.
     *
     * @return string|null
     */
    protected function vendorStoreId(): ?string
    {
        return session('adminStoreId');
    }

    /**
     * Render the list into the vendor shell. Mirrors the core
     * DataTableComponent::render() (rows + columns + viewData, then the admin
     * layout) but ALSO feeds the vendor shell's optional `$icon` header slot —
     * core passes only `title` to its layout, so the icon would otherwise be lost.
     *
     * @return View
     */
    public function render(): View
    {
        $data = array_merge([
            'rows'    => $this->rows(),
            'columns' => $this->columns(),
        ], $this->viewData());

        // WHY titleKey-first: the vendor screens always declare a language key, but
        // fall back to the core $screenTitle/$pageTitle chain for parity with base.
        $title = $this->titleKey !== null
            ? gp247_language_render($this->titleKey)
            : ($this->screenTitle ?? $this->pageTitle);

        return view($this->listView() !== '' ? $this->listView() : 'gp247-admin::partials.data-table', $data)
            ->layout($this->adminLayout, ['title' => $title, 'icon' => $this->icon]);
    }
}
