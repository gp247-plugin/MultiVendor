<?php

namespace App\GP247\Plugins\MultiVendor\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\ResourcePanel;
use GP247\Core\AdminShell\Infrastructure\HasValidationLabels;
use GP247\Core\Models\AdminCountry;
use App\GP247\Plugins\MultiVendor\Admin\Models\AdminVendorUser;
use App\GP247\Plugins\MultiVendor\Events\CreatingVendorUser;
use App\GP247\Plugins\MultiVendor\Events\CreatedVendorUser;
use App\GP247\Plugins\MultiVendor\Events\DeletingVendorUser;
use App\GP247\Plugins\MultiVendor\Events\DeletedVendorUser;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;

/**
 * Vendor user manager (MultiVendor root-admin) — two-panel screen
 * (add/edit form left, live list right) on the shared core ResourcePanel base
 * (ADR-005, ui-tailadmin P1). Livewire port of the legacy
 * AdminRootVendorUserController (controller + Blade), preserving its business
 * logic verbatim: bcrypt password, lowercase + unique email, status 0/1, the
 * store_id assignment (vendor's own store, never the ROOT store id 1), and the
 * Creating/Created/Deleting/Deleted vendor-user events.
 *
 * Access is decided by URI+method (ADR-001 Layer-2) against the base route; the
 * $permission slug is kept as a human-readable label only.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-root-admin-livewire
 * @aidlc-adr multi-vendor_admin-livewire-migration
 */
class VendorUserManager extends ResourcePanel
{
    use HasValidationLabels;

    /**
     * Permission label for this screen; the real gate is URI+method (Layer-2).
     *
     * @var string|null
     */
    protected ?string $permission = 'admin_MultiVendorUser';

    /**
     * Keep the list state (page/keyword/sort) and the just-saved record on
     * screen when editing/saving, instead of remounting via route navigation
     * (matches the modern sibling managers, e.g. SupplierManager/LinkManager).
     * persist() sets $this->editingId after a create so save() can re-fill.
     *
     * @var bool
     * @aidlc-story US-AUI-two-panel-state-preservation
     * @aidlc-adr ADR-admin-shell-rbac-two-panel-state-preservation
     */
    protected bool $keepStateOnSave = true;

    /**
     * The password must reach bcrypt() as the RAW value the vendor will type at
     * login; gp247_clean() htmlspecialchars-escapes its input, which would hash a
     * mangled string. Excluding it here mirrors the controller's
     * gp247_clean($data, ['password'], true).
     *
     * @var array<int, string>
     */
    protected array $richFields = ['password'];

    /**
     * A fresh vendor-user query. Root-admin screen: no store scoping — store_id
     * here is the vendor's OWN store assignment, not the multi-store data fence.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function baseQuery()
    {
        return AdminVendorUser::query();
    }

    /**
     * @return array<int, string> Columns matched by the keyword filter.
     */
    protected function searchable(): array
    {
        return ['id', 'first_name', 'last_name', 'email'];
    }

    /**
     * @return array<int, string> Columns the user may sort by.
     */
    protected function sortableColumns(): array
    {
        return ['first_name', 'last_name', 'email', 'status', 'created_at'];
    }

    /**
     * @return string The per-resource two-panel view name.
     */
    protected function panelView(): string
    {
        return 'Plugins/MultiVendor::Admin.screen.root.livewire.vendor_user_manager';
    }

    /**
     * @return string Screen title.
     */
    protected function pageTitle(): string
    {
        return gp247_language_render('multi_vendor.vendor_user');
    }

    /**
     * @return string The base (list/create) route name.
     */
    protected function baseRoute(): string
    {
        return 'admin_MultiVendorUser.index';
    }

    /**
     * @return array<string, mixed> Empty/default form state.
     */
    protected function formDefaults(): array
    {
        return [
            'first_name' => '',
            'last_name'  => '',
            'phone'      => '',
            'postcode'   => '',
            'email'      => '',
            'address1'   => '',
            'address2'   => '',
            'country'    => '',
            'password'   => '',
            'store_id'   => '',
            'status'     => 1,
        ];
    }

    /**
     * Populate the form from a record for editing. Password stays blank — an
     * empty password on save means "keep the current one" (controller parity).
     *
     * @param AdminVendorUser $model
     * @return array<string, mixed>
     */
    protected function fillForm($model): array
    {
        return [
            'first_name' => (string) $model->first_name,
            'last_name'  => (string) $model->last_name,
            'phone'      => (string) $model->phone,
            'postcode'   => (string) $model->postcode,
            'email'      => (string) $model->email,
            'address1'   => (string) $model->address1,
            'address2'   => (string) $model->address2,
            'country'    => (string) $model->country,
            'password'   => '',
            'store_id'   => (string) $model->store_id,
            'status'     => (int) $model->status,
        ];
    }

    /**
     * Validation rules, mirroring the controller. On create the password is
     * required; on edit it is optional (blank keeps the current one). Email is
     * required + unique (ignoring self on edit). store_id must be one of the
     * assignable (non-ROOT) stores — server-authoritative, since the dropdown
     * could be tampered with client-side.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $table        = (new AdminVendorUser())->getTable();
        $countryCodes = (new AdminCountry())->pluck('code')->toArray();
        $storeIds     = $this->assignableStoreIds();

        return [
            'form.first_name' => ['required', 'string', 'max:100'],
            'form.last_name'  => ['required', 'string', 'max:100'],
            'form.phone'      => ['nullable', 'string', 'max:20'],
            'form.postcode'   => ['nullable', 'string', 'min:5', 'max:10'],
            'form.email'      => ['required', 'string', 'email', 'max:255', Rule::unique($table, 'email')->ignore($this->editingId)],
            'form.address1'   => ['nullable', 'string', 'max:100'],
            'form.address2'   => ['nullable', 'string', 'max:100'],
            'form.country'    => ['nullable', 'string', 'min:2', Rule::in($countryCodes)],
            'form.password'   => $this->editingId !== null
                ? ['nullable', 'string', 'min:6']
                : ['required', 'string', 'min:6'],
            'form.store_id'   => ['required', Rule::in($storeIds)],
        ];
    }

    /**
     * Reuse the existing multi_vendor label keys for validator attributes so the
     * messages read exactly like the controller's hand-written ones.
     *
     * @return array<string, string>
     */
    protected function attributeLabels(): array
    {
        return [
            'form.first_name' => 'multi_vendor.first_name',
            'form.last_name'  => 'multi_vendor.last_name',
            'form.phone'      => 'multi_vendor.phone',
            'form.postcode'   => 'multi_vendor.postcode',
            'form.email'      => 'multi_vendor.email',
            'form.address1'   => 'multi_vendor.address1',
            'form.address2'   => 'multi_vendor.address2',
            'form.country'    => 'multi_vendor.country',
            'form.password'   => 'multi_vendor.password',
            'form.store_id'   => 'admin.select_store',
        ];
    }

    /**
     * Persist the sanitised form. bcrypt the password only when supplied (blank
     * on edit keeps the current one), lowercase the email, coerce status to 0/1,
     * and dispatch the Creating/Created events on insert — controller parity.
     *
     * @param array<string, mixed> $data
     * @return void
     */
    protected function persist(array $data): void
    {
        $attributes = [
            'first_name' => $data['first_name'] ?? '',
            'last_name'  => $data['last_name'] ?? '',
            'phone'      => $data['phone'] ?? '',
            'postcode'   => $data['postcode'] ?? '',
            'email'      => strtolower((string) ($data['email'] ?? '')),
            'address1'   => $data['address1'] ?? '',
            'address2'   => $data['address2'] ?? '',
            'country'    => $data['country'] ?? '',
            'store_id'   => $data['store_id'] ?? '',
            'status'     => empty($data['status']) ? 0 : 1,
        ];

        // WHY: password is excluded from gp247_clean (richFields) so it reaches
        // bcrypt raw. Only hash + write it when the admin actually entered one;
        // a blank field on edit leaves the stored hash untouched.
        $password = (string) ($data['password'] ?? '');
        if ($password !== '') {
            $attributes['password'] = bcrypt($password);
        }

        if ($this->editingId !== null) {
            AdminVendorUser::findOrFail($this->editingId)->update($attributes);

            return;
        }

        CreatingVendorUser::dispatch($attributes);
        $vendor = AdminVendorUser::create($attributes);
        CreatedVendorUser::dispatch($vendor);

        // WHY: keepStateOnSave — save() re-fills the form from the persisted row.
        $this->editingId = (string) $vendor->id;
    }

    /**
     * Delete a vendor user, dispatching the Deleting/Deleted events around the
     * destroy so listeners fire exactly as they did for the controller's bulk
     * delete (which passed an array of ids).
     *
     * @param int|string $id
     * @return void
     */
    protected function deleteModel($id): void
    {
        $ids = [$id];
        DeletingVendorUser::dispatch($ids);
        AdminVendorUser::destroy($ids);
        DeletedVendorUser::dispatch($ids);
    }

    /**
     * Assignable store ids (id => code) minus the ROOT store (id 1), which is the
     * marketplace owner and never a vendor's own store. Shared by the validation
     * whitelist and the form's store dropdown.
     *
     * @return array<int, string> List of assignable store ids (as strings).
     */
    protected function assignableStoreIds(): array
    {
        return collect(gp247_store_get_list_code())
            ->reject(fn ($code, $id) => $id == 1)
            ->keys()
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    /**
     * Render the two-panel view with the country + store option lists the form's
     * searchable-selects need.
     *
     * @return View
     */
    public function render(): View
    {
        $countries = (new AdminCountry())->getCodeAll();

        // Store options: id => code, minus the ROOT store (id 1) — see legacy blade.
        $storeOptions = collect(gp247_store_get_list_code())
            ->reject(fn ($code, $id) => $id == 1)
            ->map(fn ($code, $id) => ['id' => (string) $id, 'label' => $code])
            ->values()
            ->all();

        return view($this->panelView(), [
            'rows'           => $this->rows(),
            'countryOptions' => collect($countries)->map(fn ($v, $k) => ['id' => $k, 'label' => $v])->values()->all(),
            'storeOptions'   => $storeOptions,
        ])->layout('gp247-admin::layouts.admin', ['title' => $this->pageTitle()]);
    }
}
