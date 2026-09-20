<?php

namespace App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor;

use GP247\Core\AdminShell\Infrastructure\HasCustomFields;
use GP247\Shop\Models\ShopSupplier;
use Illuminate\Validation\Rule;

/**
 * Vendor supplier manager (Pha 2 L1 "inherit core layout" migration) — the two-panel
 * add/edit form + list screen rebuilt on the core ResourcePanel base (via
 * VendorResourcePanel) plus the shared HasCustomFields trait, replacing the
 * hand-written VendorAdminComponent version. It matches the core admin two-panel
 * shape (rule ui-tailadmin P1/P3) while keeping every MultiVendor specific: each
 * row is scoped to the signed-in vendor's own store (baseQuery + persist +
 * findOwned use vendorStoreId()), the alias auto-slugs from the name and keeps its
 * global-unique rule, and the admin custom fields (type shop_supplier) are carried
 * over via the trait. Supplier is FLAT (no per-language descriptions), so the
 * multi-language tabs pattern is intentionally dropped — the fields bind straight
 * to form.*.
 *
 * WHY per-item store scope on load/delete: the legacy controller looked rows up
 * with a bare ShopSupplier::find($id) (no store filter) — a vendor-isolation gap.
 * Every lookup here is fenced to vendorStoreId() (baseQuery/findOwned), keeping a
 * vendor unable to touch another store's supplier while preserving the observable
 * create/edit/delete behavior.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-vendor-admin-livewire
 * @aidlc-adr multi-vendor_admin-livewire-migration
 */
class VendorSupplierManager extends VendorResourcePanel
{
    use HasCustomFields;

    /**
     * Keep the list panel (page/keyword/sort) and the just-saved supplier on screen
     * when editing/saving, instead of remounting via route navigation. With this
     * on, editRow()/save() stay fully in-component, so the single /supplier route is
     * enough (no separate edit route).
     *
     * @var bool
     */
    protected bool $keepStateOnSave = true;

    /**
     * The entity type the admin custom fields belong to (HasCustomFields contract).
     *
     * @return string
     */
    protected function customFieldType(): string
    {
        return 'shop_supplier';
    }

    /**
     * Active custom-field definitions for the supplier entity. Public so the panel
     * view can render the custom-field block (parity with the legacy screen's
     * public customFields()).
     *
     * @return \Illuminate\Support\Collection
     */
    public function customFields()
    {
        return gp247_custom_field_list('shop_supplier');
    }

    /**
     * Every row is scoped to the signed-in vendor's own store — a vendor only ever
     * sees/edits/deletes its own suppliers.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function baseQuery()
    {
        // WHY: store isolation — the sole tenancy boundary for the vendor area; the
        // list, editRow(), mount() deep-link, findOwned() and deleteModel() all read
        // through this, so a vendor can never reach another store's supplier by id.
        return ShopSupplier::query()->where('store_id', $this->vendorStoreId());
    }

    /**
     * @return array<int, string> Keyword filter column (the supplier name), matching
     *                            the legacy screen's name search.
     */
    protected function searchable(): array
    {
        return ['name'];
    }

    /**
     * @return array<int, string>
     */
    protected function sortableColumns(): array
    {
        return ['name', 'sort', 'id'];
    }

    /**
     * @return array<int, string> Newest first, matching the legacy id__desc default.
     */
    protected function defaultSort(): array
    {
        return ['id', 'desc'];
    }

    /**
     * @return array<string, mixed> Empty/default form state (no status column on
     *                              this entity).
     */
    protected function formDefaults(): array
    {
        return [
            'image' => '',
            'name' => '',
            'alias' => '',
            'phone' => '',
            'url' => '',
            'email' => '',
            'address' => '',
            'sort' => 0,
        ];
    }

    /**
     * Reset both the scalar form and the custom-field editing state.
     *
     * @return void
     */
    public function resetForm(): void
    {
        parent::resetForm();
        $this->initCustomFields();
    }

    /**
     * @param ShopSupplier $model
     * @return array<string, mixed>
     */
    protected function fillForm($model): array
    {
        // WHY: side-effect load of the custom-field values so the block re-populates
        // on edit and after save (keepStateOnSave re-fills through fillForm).
        $this->loadCustomFields($model->id);

        return [
            'image' => (string) $model->image,
            'name' => (string) $model->name,
            'alias' => (string) $model->alias,
            'phone' => (string) $model->phone,
            'url' => (string) $model->url,
            'email' => (string) $model->email,
            'address' => (string) $model->address,
            'sort' => (int) $model->sort,
        ];
    }

    /**
     * Validation rules — same shape as the legacy VendorSupplierController, plus a
     * `required` rule for each required custom field (definition-driven). The alias
     * is GLOBAL-unique (not per-store), ignoring the current row while editing,
     * exactly as the legacy screen.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $aliasUnique = Rule::unique(ShopSupplier::class, 'alias');
        if ($this->editingId) {
            $aliasUnique = $aliasUnique->ignore($this->editingId, 'id');
        }

        return array_merge([
            'form.image' => 'required',
            'form.sort' => 'numeric|min:0',
            'form.name' => 'required|string|max:100',
            'form.alias' => ['required', 'regex:/(^([0-9A-Za-z\-_]+)$)/', 'string', 'max:100', $aliasUnique],
            'form.url' => 'url|nullable',
            'form.email' => 'email|nullable',
        ], $this->customFieldRules());
    }

    /**
     * Human-friendly validation messages (mirrors the legacy controller overrides).
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'form.name.required' => gp247_language_render('validation.required', ['attribute' => gp247_language_render('admin.supplier.name')]),
            'form.alias.regex' => gp247_language_render('admin.supplier.alias_validate'),
        ];
    }

    /**
     * Default the alias from the name, then slugify + cap length (like the legacy
     * controller) before the secure save pipeline.
     *
     * @return void
     */
    public function save(): void
    {
        if (empty($this->form['alias'])) {
            $this->form['alias'] = $this->form['name'] ?? '';
        }
        $this->form['alias'] = gp247_word_limit(gp247_word_format_url((string) $this->form['alias']), 100);

        parent::save();
    }

    /**
     * Insert/update the supplier scoped to the vendor's store, then persist its
     * custom fields. Mirrors the legacy postCreate/postEdit.
     *
     * @param array<string, mixed> $data
     * @return void
     */
    protected function persist(array $data): void
    {
        $attributes = [
            'image' => $data['image'] ?? '',
            'name' => $data['name'] ?? '',
            'alias' => $data['alias'] ?? '',
            'url' => $data['url'] ?? '',
            'email' => $data['email'] ?? '',
            'address' => $data['address'] ?? '',
            'phone' => $data['phone'] ?? '',
            'sort' => (int) ($data['sort'] ?? 0),
        ];

        if ($this->editingId !== null) {
            $supplier = $this->findOwned($this->editingId);
            if ($supplier === null) {
                $this->notify('error', gp247_language_render('admin.data_notfound'));

                return;
            }
            // store_id is not reassigned on edit (matches the legacy update payload;
            // baseQuery already fenced the lookup to the vendor's store).
            $supplier->update($attributes);
        } else {
            // WHY: store ownership is server-authoritative — the new row belongs to
            // the signed-in vendor's store, never a client-supplied value.
            $attributes['store_id'] = $this->vendorStoreId();
            $supplier = ShopSupplier::create($attributes);
        }

        // WHY: keepStateOnSave — expose the persisted id (incl. after create) so
        // ResourcePanel::save() can re-fill the form and keep it on screen.
        $this->editingId = (string) $supplier->id;

        gp247_custom_field_update($this->customFieldsPayload(), $supplier->id, 'shop_supplier');
    }

    /**
     * Delete one supplier the vendor owns (store-scoped find); the model's deleting
     * hook clears its custom fields.
     *
     * @param int|string $id
     * @return void
     */
    protected function deleteModel($id): void
    {
        $model = $this->findOwned($id);
        if ($model !== null) {
            $model->delete();
        }
    }

    /**
     * Look one supplier up, fenced to the signed-in vendor's store (legacy parity).
     *
     * @param int|string $id
     * @return ShopSupplier|null
     */
    protected function findOwned($id): ?ShopSupplier
    {
        return $this->baseQuery()->where('id', $id)->first();
    }

    /**
     * @return string
     */
    protected function panelView(): string
    {
        return 'Plugins/MultiVendor::Admin.screen.vendor.livewire.supplier_panel';
    }

    /**
     * @return string
     */
    protected function pageTitle(): string
    {
        return gp247_language_render('admin.supplier.list');
    }

    /**
     * @return string
     */
    protected function pageIcon(): string
    {
        return 'fa fa-indent';
    }

    /**
     * The single list/create/edit route (edit is in-component via ?edit=<id>, so no
     * separate <baseRoute>.edit route is needed).
     *
     * @return string
     */
    protected function baseRoute(): string
    {
        return 'vendor_admin_supplier.index';
    }
}
