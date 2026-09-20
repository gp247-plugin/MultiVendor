<?php

namespace App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor;

use Livewire\Component;

/**
 * Base for MultiVendor vendor-admin Livewire screens (Pha 2 migration).
 *
 * The vendor area authenticates against the `vendor` guard and renders its own
 * shell (Plugins/MultiVendor::Admin.layout), so it does NOT extend the core
 * GP247AdminComponent — that base authorizes against the admin guard's RBAC
 * Layer-2 and renders the staff admin layout. Access here is already gated by the
 * route's `vendor` + `checkVendorActive` + `checkStoreExist` middleware, so no
 * per-component authorization runs; a component only needs the vendor store
 * context and the shared toast channel.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-vendor-admin-livewire
 * @aidlc-adr multi-vendor_admin-livewire-migration
 */
abstract class VendorAdminComponent extends Component
{
    /** The plugin's vendor admin layout (shared shell). */
    protected const LAYOUT = 'Plugins/MultiVendor::Admin.layout';

    /**
     * The store the signed-in vendor owns. The AdminStoreId middleware sets it at
     * login (session `adminStoreId`); every vendor query is scoped to it.
     *
     * @return string|null
     */
    protected function vendorStoreId(): ?string
    {
        return session('adminStoreId');
    }

    /**
     * Emit a toast through the shared <x-gp247::toast> channel (ADR-005) — the
     * same `notify` browser event GP247AdminComponent and mvp-admin.js dispatch.
     *
     * @param string $type    One of info|success|warning|error.
     * @param string $message Human-readable text.
     * @return void
     */
    protected function notify(string $type, string $message): void
    {
        $this->dispatch('notify', type: $type, message: $message);
    }

    /**
     * Screen title rendered in the vendor shell header.
     *
     * @return string
     */
    abstract protected function pageTitle(): string;

    /**
     * Optional Font Awesome icon class for the header.
     *
     * @return string
     */
    protected function pageIcon(): string
    {
        return '';
    }

    /**
     * Render a view into the vendor shell layout.
     *
     * @param string $view
     * @param array<string, mixed> $data
     * @return \Illuminate\Contracts\View\View
     */
    protected function renderInShell(string $view, array $data = [])
    {
        return view($view, $data)->layout(self::LAYOUT, [
            'title' => $this->pageTitle(),
            'icon' => $this->pageIcon(),
        ]);
    }
}
