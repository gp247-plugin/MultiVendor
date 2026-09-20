<?php

namespace App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor;

use App\GP247\Plugins\MultiVendor\Admin\Models\AdminVendorCategory;
use App\GP247\Plugins\MultiVendor\Models\VendorCategoryDescription;
use GP247\Core\AdminShell\Infrastructure\HasMultilingualDescriptions;
use GP247\Core\AdminShell\Infrastructure\HasValidationLabels;
use GP247\Core\Models\AdminLanguage;

/**
 * Vendor category manager (Pha 2 L1 "inherit core layout" pilot) — the two-panel
 * add/edit form + list screen rebuilt on the core ResourcePanel base (via
 * VendorResourcePanel) plus the shared multilingual-descriptions trait, replacing
 * the hand-written VendorAdminComponent version. It matches the core shop
 * CategoryManager shape (rule ui-tailadmin P1/P3) while keeping every
 * MultiVendor specific: each row is scoped to the signed-in vendor's own store
 * (baseQuery + persist use vendorStoreId()), the alias auto-slugs from the first
 * language title, the per-language rows live in vendor_category_description
 * (FK vendor_category_id, columns title/keyword/description), the LFM media type
 * is vendor_category_store, and the cache_category_store cache is flushed on
 * write/delete. The vendor category has NO parent column, so the core template's
 * parent select + store-picker chrome are intentionally dropped.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-vendor-admin-livewire
 * @aidlc-adr multi-vendor_admin-livewire-migration
 */
class VendorCategoryManager extends VendorResourcePanel
{
    use HasMultilingualDescriptions;
    use HasValidationLabels;

    /**
     * Keep the list panel (page/keyword/sort) and the just-saved category on
     * screen when editing/saving, instead of remounting via route navigation.
     * With this on, editRow()/save() stay fully in-component, so the single
     * /category-mng route is enough (no separate edit route).
     *
     * @var bool
     */
    protected bool $keepStateOnSave = true;

    /**
     * Translatable columns stored in vendor_category_description (identical to the
     * legacy vendor screen — NOT the core screen's `name`).
     *
     * @return array<int, string>
     */
    protected function multilingualFields(): array
    {
        return ['title', 'keyword', 'description'];
    }

    /**
     * @return class-string The vendor description model.
     */
    protected function descriptionModelClass(): string
    {
        return VendorCategoryDescription::class;
    }

    /**
     * @return string The vendor description table foreign key.
     */
    protected function descriptionForeignKey(): string
    {
        return 'vendor_category_id';
    }

    /**
     * Every row is scoped to the signed-in vendor's own store — a vendor only ever
     * sees/edits/deletes its own categories.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function baseQuery()
    {
        // WHY: store isolation — the sole tenancy boundary for the vendor area; the
        // list, editRow(), mount() deep-link and deleteModel() all read through this,
        // so a vendor can never reach another store's category by id.
        return AdminVendorCategory::query()->where('store_id', $this->vendorStoreId());
    }

    /**
     * @return array<int, string> Alias is the only scalar column (title lives in
     *                            the description table — parity with core shop).
     */
    protected function searchable(): array
    {
        return ['alias'];
    }

    /**
     * @return array<int, string>
     */
    protected function sortableColumns(): array
    {
        return ['alias', 'sort', 'id'];
    }

    /**
     * @return array<int, string> Newest first, matching the legacy vendor screen.
     */
    protected function defaultSort(): array
    {
        return ['id', 'desc'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function formDefaults(): array
    {
        return ['image' => '', 'alias' => '', 'sort' => 0, 'status' => 1];
    }

    /**
     * Reset both the scalar form and the per-language description state.
     *
     * @return void
     */
    public function resetForm(): void
    {
        parent::resetForm();
        $this->initDescriptions();
    }

    /**
     * @param AdminVendorCategory $model
     * @return array<string, mixed>
     */
    protected function fillForm($model): array
    {
        $this->fillDescriptions($model->descriptions);

        return [
            'image' => (string) $model->image,
            'alias' => (string) $model->alias,
            'sort' => (int) $model->sort,
            'status' => (int) $model->status,
        ];
    }

    /**
     * Same shape as the legacy VendorCategoryController: alias is optional (derived
     * from the title when blank), no global unique — aliases are per-store, so a
     * cross-store uniqueness rule would wrongly block a second store reusing a slug.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'form.alias' => ['nullable', 'regex:/(^([0-9A-Za-z\-_]+)$)/', 'string', 'max:100'],
            'form.sort' => ['nullable', 'numeric', 'min:0'],
            'desc.*.title' => ['required', 'string', 'max:200'],
            'desc.*.keyword' => ['nullable', 'string', 'max:200'],
            'desc.*.description' => ['nullable', 'string', 'max:300'],
        ];
    }

    /**
     * Route the raw "form.*" / "desc.*" validator paths through the vendor
     * category_store language keys so error messages read the real field name.
     *
     * @return array<string, string>
     */
    protected function attributeLabels(): array
    {
        return [
            'form.alias' => 'Plugins/MultiVendor::category_store.alias',
            'form.sort' => 'Plugins/MultiVendor::category_store.sort',
            'desc.*.title' => 'Plugins/MultiVendor::category_store.title',
            'desc.*.keyword' => 'Plugins/MultiVendor::category_store.keyword',
            'desc.*.description' => 'Plugins/MultiVendor::category_store.description',
        ];
    }

    /**
     * Derive the alias from the first language's title when left blank, then
     * slugify (parity with the legacy controller) before the secure save pipeline.
     *
     * @return void
     */
    public function save(): void
    {
        if (empty($this->form['alias'])) {
            $firstLang = $this->firstDescriptionLanguage();
            $this->form['alias'] = $firstLang !== null ? ($this->desc[$firstLang]['title'] ?? '') : '';
        }
        $this->form['alias'] = gp247_word_limit(gp247_word_format_url((string) $this->form['alias']), 100);

        parent::save();
    }

    /**
     * Insert/update the category scoped to the vendor's store, then rewrite its
     * per-language descriptions and flush the store category cache.
     *
     * @param array<string, mixed> $data
     * @return void
     */
    protected function persist(array $data): void
    {
        $attributes = [
            'image' => $data['image'] ?? '',
            'alias' => $data['alias'],
            'status' => empty($data['status']) ? 0 : 1,
            'sort' => (int) ($data['sort'] ?? 0),
        ];

        if ($this->editingId !== null) {
            // WHY: re-fetch through the store-scoped baseQuery so an edit can only
            // ever land on a category the signed-in vendor owns (store_id immutable).
            $category = $this->baseQuery()->findOrFail($this->editingId);
            $category->update($attributes);
        } else {
            // WHY: store ownership is server-authoritative — the new row belongs to
            // the signed-in vendor's store, never a client-supplied value.
            $attributes['store_id'] = $this->vendorStoreId();
            $category = AdminVendorCategory::create($attributes);
        }

        // WHY: keepStateOnSave — expose the persisted id (incl. after create) so
        // ResourcePanel::save() can re-fill the form and keep it on screen.
        $this->editingId = (string) $category->id;

        $this->saveDescriptions($category->id);

        // WHY: keep the vendor store category title cache coherent (legacy parity).
        gp247_cache_clear('cache_category_store');
    }

    /**
     * Delete one category the vendor owns (store-scoped find), cascading its
     * descriptions + product pivots via VendorCategory::boot().
     *
     * @param int|string $id
     * @return void
     */
    protected function deleteModel($id): void
    {
        $model = $this->baseQuery()->find($id);
        if ($model !== null) {
            $model->delete();
            gp247_cache_clear('cache_category_store');
        }
    }

    /**
     * @return string
     */
    protected function panelView(): string
    {
        return 'Plugins/MultiVendor::Admin.screen.vendor.livewire.category_panel';
    }

    /**
     * @return string
     */
    protected function pageTitle(): string
    {
        return gp247_language_render('Plugins/MultiVendor::category_store.admin.list');
    }

    /**
     * @return string
     */
    protected function pageIcon(): string
    {
        return 'fa fa-indent';
    }

    /**
     * The single list/create/edit route (edit is in-component via ?edit=<id>, so
     * no separate <baseRoute>.edit route is needed).
     *
     * @return string
     */
    protected function baseRoute(): string
    {
        return 'vendor_admin_category.index';
    }

    /**
     * Active languages (code => language model) for the per-language tabs.
     *
     * @return array<string, mixed>
     */
    public function languages(): array
    {
        return AdminLanguage::getListActive()->all();
    }
}
