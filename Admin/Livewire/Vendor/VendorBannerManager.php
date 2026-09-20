<?php

namespace App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor;

use GP247\Front\Models\FrontBanner;
use GP247\Front\Models\FrontBannerType;

/**
 * Vendor banner manager (Pha 2 L1 "inherit core layout" migration) — the two-panel
 * add/edit form + list screen rebuilt on the core ResourcePanel base (via
 * VendorResourcePanel), replacing the hand-written VendorAdminComponent version.
 * It matches the core admin two-panel shape (rule ui-tailadmin P1/P3) while keeping
 * every MultiVendor specific: each row is scoped to the signed-in vendor's own
 * store (baseQuery + persist use vendorStoreId()), and the flat banner fields
 * (image, url, title, html, type, target, sort, status) are preserved from the
 * controller. Banner has NO per-language descriptions, so the multi-language tabs
 * pattern is intentionally dropped — the fields bind straight to form.*.
 *
 * Store ownership: the front store pivots were retired for a scalar `store_id`
 * column (front 1-1 ownership migration 2026_08_28_090000). persist() writes
 * `store_id` on create, which is exactly how every banner read/delete scopes by
 * store; baseQuery() fences reads/edits/deletes to the vendor's own store.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-vendor-admin-livewire
 * @aidlc-adr multi-vendor_admin-livewire-migration
 */
class VendorBannerManager extends VendorResourcePanel
{
    /**
     * Keep the list panel (page/keyword/sort) and the just-saved banner on screen
     * when editing/saving, instead of remounting via route navigation. With this
     * on, editRow()/save() stay fully in-component, so the single /banner route is
     * enough (no separate edit route).
     *
     * @var bool
     */
    protected bool $keepStateOnSave = true;

    /**
     * The `html` field holds raw markup the vendor writes by hand; exclude it from
     * gp247_clean's htmlspecialchars pass in ResourcePanel::save() so the snippet
     * survives as-is (parity with the legacy controller, which stored it raw).
     *
     * @var array<int, string>
     */
    protected array $richFields = ['html'];

    /**
     * Available link targets, keyed by value (same as the legacy controller). Public
     * so the panel view can build the target select.
     *
     * @return array<string, string>
     */
    public function arrTarget(): array
    {
        return ['_blank' => '_blank', '_self' => '_self'];
    }

    /**
     * Banner types keyed by code. The two store-scoped types are only offered when
     * the marketplace plugin is globally enabled, exactly as the legacy
     * VendorBannerController::__construct built the list. Public so the panel view
     * can build the type select.
     *
     * @return array<string, string>
     */
    public function dataType(): array
    {
        $dataType = (new FrontBannerType)->pluck('name', 'code')->all();
        if (gp247_config_global('MultiVendor')) {
            $dataType['background-store'] = 'Background store';
            $dataType['breadcrumb-store'] = 'Breadcrumb store';
        }
        ksort($dataType);

        return $dataType;
    }

    /**
     * Every row is scoped to the signed-in vendor's own store — a vendor only ever
     * sees/edits/deletes its own banners.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function baseQuery()
    {
        // WHY: store isolation — the sole tenancy boundary for the vendor area; the
        // list, editRow(), mount() deep-link and deleteModel() all read through this,
        // so a vendor can never reach another store's banner by id.
        return FrontBanner::query()->where('store_id', $this->vendorStoreId());
    }

    /**
     * @return array<int, string> Keyword filter column. `name` is the real text
     *                            column the legacy getBannerListAdmin searched (the
     *                            front_banner table has no `title` column).
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
        return ['sort', 'id'];
    }

    /**
     * @return array<int, string> Newest first, matching the legacy id__desc default.
     */
    protected function defaultSort(): array
    {
        return ['id', 'desc'];
    }

    /**
     * Empty form state; the type defaults to the first available option, mirroring
     * the legacy non-clearable type select which always carries a value.
     *
     * @return array<string, mixed>
     */
    protected function formDefaults(): array
    {
        $types = $this->dataType();

        return [
            'image' => '',
            'url' => '',
            'name' => '',
            'html' => '',
            'type' => (string) array_key_first($types),
            'target' => '_self',
            'sort' => 0,
            'status' => true,
        ];
    }

    /**
     * @param FrontBanner $model
     * @return array<string, mixed>
     */
    protected function fillForm($model): array
    {
        return [
            'image' => (string) $model->image,
            'url' => (string) $model->url,
            'name' => (string) $model->name,
            'html' => (string) $model->html,
            'type' => (string) $model->type,
            'target' => (string) $model->target,
            'sort' => (int) $model->sort,
            'status' => (bool) $model->status,
        ];
    }

    /**
     * Validation rules — same meaningful constraint as the legacy
     * VendorBannerController::postCreate() (sort must be a non-negative number).
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'form.sort' => 'numeric|min:0',
        ];
    }

    /**
     * Insert/update the banner scoped to the vendor's store. Mirrors the legacy
     * postCreate/postEdit, replacing the retired stores() pivot with the scalar
     * store_id column.
     *
     * @param array<string, mixed> $data
     * @return void
     */
    protected function persist(array $data): void
    {
        $attributes = [
            'image' => $data['image'] ?? '',
            'url' => $data['url'] ?? '',
            'name' => $data['name'] ?? '',
            'html' => $data['html'] ?? '',
            // WHY: fall back to 0 like the legacy `$data['type'] ?? 0` when no type
            // is selected.
            'type' => ($data['type'] ?? '') !== '' ? $data['type'] : 0,
            'target' => $data['target'] ?? '_self',
            'status' => empty($data['status']) ? 0 : 1,
            'sort' => (int) ($data['sort'] ?? 0),
        ];

        if ($this->editingId !== null) {
            // WHY: re-fetch through the store-scoped baseQuery so an edit can only
            // ever land on a banner the signed-in vendor owns (store_id immutable).
            $banner = $this->baseQuery()->findOrFail($this->editingId);
            $banner->update($attributes);
        } else {
            // WHY: store ownership is server-authoritative — the new row belongs to
            // the signed-in vendor's store, never a client-supplied value.
            $attributes['store_id'] = $this->vendorStoreId();
            $banner = FrontBanner::createBannerAdmin($attributes);
        }

        // WHY: keepStateOnSave — expose the persisted id (incl. after create) so
        // ResourcePanel::save() can re-fill the form and keep it on screen.
        $this->editingId = (string) $banner->id;
    }

    /**
     * Delete one banner the vendor owns (store-scoped find); the model's deleting
     * hook clears its custom fields.
     *
     * @param int|string $id
     * @return void
     */
    protected function deleteModel($id): void
    {
        $model = $this->baseQuery()->find($id);
        if ($model !== null) {
            $model->delete();
        }
    }

    /**
     * @return string
     */
    protected function panelView(): string
    {
        return 'Plugins/MultiVendor::Admin.screen.vendor.livewire.banner_panel';
    }

    /**
     * @return string
     */
    protected function pageTitle(): string
    {
        return gp247_language_render('admin.banner.list');
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
        return 'vendor_admin_banner.index';
    }
}
