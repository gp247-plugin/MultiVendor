<?php

namespace App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor;

use GP247\Core\AdminShell\Http\Livewire\WebsiteInfo;
use Illuminate\Contracts\View\View;

/**
 * Vendor store-info screen — the signed-in vendor edits their OWN store, reusing
 * the core WebsiteInfo screen wholesale so the layout matches the core admin
 * store-settings screen exactly (Pha 2 L1 "inherit core layout" migration, the
 * final consistency fix). Inheriting the concrete core component gives the vendor
 * the same inline-edit machinery (mount/loadStore, updatedStore live persist,
 * per-language descriptions via updatedDesc) for free, and re-points only the
 * seams that differ for the marketplace vendor area:
 *
 *   1. Authorization — the vendor area authenticates against the `vendor` guard
 *      and is gated by the route middleware (vendor + checkVendorActive +
 *      checkStoreExist), NOT the admin RBAC Layer-2 the core base enforces, so
 *      both authorize hooks are neutralised here.
 *   2. Store — the effective store is resolved by the INHERITED (private)
 *      storeId(): storeScopeActive() is true and the vendor's session
 *      `adminStoreId` is a sub-store (isRootScope() === false), so storeId()
 *      returns storeContext() = the vendor's own store. No {id} on the route and
 *      no client-supplied store id ⇒ a forged payload can never retarget another
 *      store (server-authoritative).
 *   3. Allow-list (SECURITY) — a marketplace vendor may edit ONLY media + contact
 *      fields and the per-language descriptions. currency / language / template /
 *      domain / code are platform-owned and must be neither rendered nor writable
 *      (single-domain marketplace model). The write allow-list is enforced in
 *      updatedStore() below and the forbidden rows are dropped from the vendor
 *      blade; the destructive template switch is blocked server-side too.
 *   4. Layout — render() mirrors the parent but points at the vendor blade and the
 *      plugin's vendor shell (ADR-007, rule ui-tailadmin P3).
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-vendor-admin-livewire
 * @aidlc-adr multi-vendor_admin-livewire-migration
 */
class VendorStoreInfoForm extends WebsiteInfo
{
    /** The plugin's vendor admin shell (replaces the core admin layout). */
    protected const VENDOR_LAYOUT = 'Plugins/MultiVendor::Admin.layout';

    /**
     * Store fields a marketplace vendor may write — media + contact only. This is
     * a SECURITY boundary that mirrors the forbidden set: currency / language /
     * template / domain / code are deliberately absent and must never be added.
     *
     * WHY hardcoded here: the equivalent MEDIA/FIELDS consts on the parent
     * (WebsiteInfo) are private and cannot be reused from a subclass.
     */
    private const VENDOR_WRITABLE_FIELDS = [
        'logo', 'icon', 'og_image',
        'phone', 'long_phone', 'email', 'time_active', 'address', 'office', 'warehouse',
    ];

    /**
     * No admin RBAC on read — the route's vendor middleware already gated access.
     *
     * @return void
     */
    protected function authorizeView(): void
    {
    }

    /**
     * No admin RBAC on mutating actions — the same route-middleware gate applies,
     * and every write is store-scoped to the signed-in vendor's own store.
     *
     * @param string $method
     * @return void
     */
    protected function authorizeAction(string $method): void
    {
    }

    /**
     * Persist a scalar store field the moment it changes — but ONLY for the vendor
     * allow-list. This is the enforcement point of the security boundary: any key
     * outside media + contact (currency / language / template / domain / code) is
     * hard-rejected BEFORE the parent runs, so the parent's domain and destructive
     * template branches are never reachable from the vendor screen even if the
     * payload were forged (the client can submit any store.<key>).
     *
     * @param mixed  $value
     * @param string $key   The changed `store.<key>` segment.
     * @return void
     */
    public function updatedStore($value, string $key): void
    {
        // WHY: platform-owned fields (currency/language/template/domain/code) must
        // never be writable by a vendor in the single-domain marketplace model.
        if (!in_array($key, self::VENDOR_WRITABLE_FIELDS, true)) {
            return;
        }

        parent::updatedStore($value, $key);
    }

    /**
     * Block the destructive template switch entirely. `pendingTemplate` is a public
     * property (client-writable), so a forged Livewire request could set it and
     * call this method directly to run removeStore()/setupStore() — reseeding layout
     * blocks + banners on the vendor's store. Template is platform-owned, so this is
     * a hard no-op regardless of any client-set pendingTemplate.
     *
     * @return void
     */
    public function confirmTemplateSwitch(): void
    {
    }

    /**
     * Block the store Active / maintenance toggle. Decision Q3-A: a marketplace
     * vendor has no maintenance control, so the toggle is removed from the blade;
     * `active` is a public property with its own update hook, so neutralise the
     * server side too — otherwise a forged request could flip the store's live
     * state, leaving the policy only cosmetic.
     *
     * @return void
     */
    public function updatedActive(): void
    {
    }

    /**
     * Render the store-info screen into the vendor shell. Mirrors the parent render
     * but (a) points at the vendor blade, (b) forces currency / template options
     * empty and isRoot false so those rows (and the domain row) can never appear,
     * and (c) uses the plugin's vendor layout. languageOptions is still passed for
     * parity, though the vendor blade drives its description tabs from languages().
     *
     * @return View
     */
    public function render(): View
    {
        $languages = $this->languages();

        $languageOptions = [];
        foreach ($languages as $code => $lang) {
            $languageOptions[$code] = $lang->name;
        }

        return view('Plugins/MultiVendor::Admin.screen.vendor.livewire.store_info', [
            'languages' => $languages,
            'mediaFields' => ['logo', 'icon', 'og_image'],
            'fields' => ['phone', 'long_phone', 'email', 'time_active', 'address', 'office', 'warehouse'],
            'languageOptions' => $languageOptions,
            // WHY empty / false: currency, template and domain are platform-owned —
            // dropping the options AND the rows themselves (in the blade) keeps them
            // off the screen defence-in-depth, not just visually hidden.
            'currencyOptions' => [],
            'templateOptions' => [],
            'isRoot' => false,
        ])->layout(self::VENDOR_LAYOUT, [
            'title' => gp247_language_render('multi_vendor.update_info_store_title'),
            'icon' => 'fa fa-store',
        ]);
    }
}
