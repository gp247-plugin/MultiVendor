<?php

namespace App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor;

use App\GP247\Plugins\MultiVendor\Kyc\Kyc;
use App\GP247\Plugins\MultiVendor\Plans\Plan;
use App\GP247\Plugins\MultiVendor\Models\VendorProductCategory;
use Illuminate\Contracts\View\View;

/**
 * Vendor product manager (Pha 2 L1 "inherit core layout" — the final screen of the
 * admin -> Livewire migration). It INHERITS the core shop two-panel ProductManager
 * wholesale — the tabbed add/edit form (General / Description / Custom field /
 * Attribute group) on the left, the store-scoped list on the right, the Kind
 * buttons (Single / Bundle / Group), and all seven product concerns
 * (descriptions / custom fields / images / variants / composition / pricing /
 * tags) — so the vendor product screen looks and behaves exactly like the core
 * admin ProductManager (rule ui-tailadmin P1/P3, ADR-007).
 *
 * A vendor product IS a ShopProduct owned by the vendor's own store: the inherited
 * baseQuery() scopes to storeId() (session `adminStoreId`) because the vendor's
 * context is never the root scope, and resolveCreateStore() returns that same store
 * on create — so every read/write stays inside the signed-in vendor's store with no
 * extra code. Only the vendor seams are overridden here:
 *
 *   1. Authorization — the vendor area authenticates against the `vendor` guard and
 *      is gated by the route middleware (vendor + checkVendorActive +
 *      checkStoreExist), NOT the admin RBAC Layer-2 the core base enforces, so both
 *      authorize hooks are neutralised (mirrors VendorResourcePanel, which this
 *      class cannot extend since it must extend the concrete ProductManager).
 *   2. Layout / view / route — the same panel view is rendered into the plugin's
 *      vendor shell, from the vendor product view, back to the vendor list route.
 *   3. Vendor extras — the required `vendor_category_id` (stored 1-1 in
 *      vendor_product_category) and the marketplace approve gate
 *      (MultiVendor_product_auto_approve).
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-vendor-admin-livewire
 * @aidlc-adr multi-vendor_admin-livewire-migration
 */
class VendorProductManager extends \GP247\Shop\Admin\Livewire\ProductManager
{
    /** The plugin's vendor admin shell (replaces the core admin layout). */
    protected const VENDOR_LAYOUT = 'Plugins/MultiVendor::Admin.layout';

    /**
     * No admin RBAC on read — the route's vendor middleware already gated access
     * (vendor + checkVendorActive + checkStoreExist).
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
     * Render the core product panel view into the vendor shell (the core base
     * hardcodes the admin layout; only the layout / view differ).
     *
     * @return View
     */
    public function render(): View
    {
        return view($this->panelView(), [
            'rows' => $this->rows(),
        ])->layout(self::VENDOR_LAYOUT, [
            'title' => $this->pageTitle(),
            'icon' => 'fa fa-cube',
        ]);
    }

    /**
     * @return string The vendor copy of the core product-manager panel view.
     */
    protected function panelView(): string
    {
        return 'Plugins/MultiVendor::Admin.screen.vendor.livewire.product_manager';
    }

    /**
     * The single vendor list/create route (edit is in-component via ?edit=<id>,
     * inherited keepStateOnSave), so no separate <baseRoute>.edit route is needed.
     *
     * @return string
     */
    protected function baseRoute(): string
    {
        return 'vendor_admin_product.index';
    }

    /**
     * Core product defaults plus the vendor-only category field, so wire:model has
     * an initial value on create.
     *
     * @return array<string, mixed>
     */
    protected function formDefaults(): array
    {
        return array_merge(parent::formDefaults(), ['vendor_category_id' => '']);
    }

    /**
     * Core product form plus the current vendor category (1-1 via
     * vendor_product_category).
     *
     * @param \GP247\Shop\Models\ShopProduct $model
     * @return array<string, mixed>
     */
    protected function fillForm($model): array
    {
        $form = parent::fillForm($model);
        $form['vendor_category_id'] = (string) optional(
            (new VendorProductCategory)->where('product_id', $model->id)->first()
        )->vendor_category_id;

        return $form;
    }

    /**
     * Core product rules plus the required vendor category (only when the
     * MultiVendor marketplace is active — parity with the legacy
     * VendorProductController::validateAttribute()). Keyed on the same `form.*`
     * property path the core screen uses for form fields.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = parent::rules();
        if (function_exists('gp247_config_global') && gp247_config_global('MultiVendor')) {
            $rules['form.vendor_category_id'] = ['required'];
        }

        return $rules;
    }

    /**
     * Persist the product through the inherited pipeline (main row + descriptions +
     * custom fields + categories + images + variants + composition + pricing +
     * tags, store_id forced to the vendor's own store by resolveCreateStore()),
     * with two vendor extras:
     *
     *   - approve gate: when the marketplace does not auto-approve, force the
     *     payload's `approve` to 0 BEFORE the parent maps it to the product row, so
     *     the inherited productAttributes() actually persists the product as
     *     unapproved (the blade's approve checkbox cannot override the gate).
     *   - vendor category: after the parent sets $this->editingId (create + edit),
     *     rewrite the 1-1 vendor_product_category pivot for this product.
     *
     * @param array<string, mixed> $data Sanitised form.
     * @return void
     */
    protected function persist(array $data): void
    {
        // S3-4: plan product cap — only CREATING is capped ($this->editingId is still
        // empty here for a new product); editing an existing product never is.
        if (!$this->editingId && Plan::productLimitReached((string) $this->vendorStoreId())) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'form.name' => gp247_language_render('multi_vendor.plan.limit_reached', ['max' => (int) Plan::maxProducts((string) $this->vendorStoreId())]),
            ]);
        }

        // WHY: marketplace review gate — a vendor may not self-approve unless the
        // marketplace turns auto-approve on. Force it into the payload the inherited
        // productAttributes() reads (approve = empty($data['approve']) ? 0 : 1), so
        // the product is stored unapproved regardless of the submitted checkbox.
        $needsReview = !gp247_config_global('MultiVendor_product_auto_approve')
            // S3-2 (Q1-B): an unverified store never self-publishes while KYC is required,
            // even with marketplace auto-approve on.
            || Kyc::blocks((string) $this->vendorStoreId());
        if ($needsReview) {
            $data['approve'] = 0;
        }

        parent::persist($data);

        // E2b (S1-2): the marketplace owner learns a product waits for approval.
        if ($needsReview && $this->editingId) {
            \App\GP247\Plugins\MultiVendor\Notifications\VendorNotifier::productPending(
                (string) session('adminStoreId'),
                (string) $this->editingId,
                (string) ($data['sku'] ?? '')
            );
        }

        // WHY: 1-1 vendor category — rewrite the pivot (delete-then-insert) for the
        // just-persisted product. $this->editingId is set by the parent for both
        // create and edit. Query-builder insert mirrors the legacy controller and
        // sidesteps the composite-key model's non-incrementing insert semantics.
        $pivot = new VendorProductCategory();
        $pivot->where('product_id', $this->editingId)->delete();
        $pivot->insert([
            'product_id' => $this->editingId,
            'vendor_category_id' => $this->form['vendor_category_id'] ?? '',
        ]);
    }

    /**
     * Vendor category options (id => title) for the General-tab picker, scoped to
     * the signed-in vendor's store by the underlying model.
     *
     * @return array<int|string, string>
     */
    public function vendorCategoryOptions(): array
    {
        return function_exists('gp247_vendor_get_categories_admin')
            ? (array) gp247_vendor_get_categories_admin()
            : [];
    }
}
