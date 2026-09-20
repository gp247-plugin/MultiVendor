<?php
#App\GP247\Plugins\MultiVendor\Admin\Livewire\VendorStoreConfigForm.php

namespace App\GP247\Plugins\MultiVendor\Admin\Livewire;

use App\GP247\Plugins\MultiVendor\AppConfig;
use App\GP247\Plugins\MultiVendor\Commission\CommissionPolicy;
use App\GP247\Plugins\MultiVendor\Plans\Plan;
use GP247\Core\AdminShell\Infrastructure\GP247AdminComponent;
use GP247\Core\Models\AdminLanguage;
use GP247\Core\Models\AdminStore;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

/**
 * Per-vendor-store configuration screen (v2 Livewire port of
 * AdminRootVendorStoreController@config + the @updateStore inline writer, whose
 * config_info tab is the only surviving tab). Follows the MultiStore
 * StoreConfigManager cutover pattern, parameterized by store id: every field
 * persists immediately inside the component, replacing the removed v1 core
 * endpoints (admin_store.update).
 *
 * Single-domain marketplace scope (mod 20260908T201252): the partner mail /
 * captcha / display tabs are gone, so this screen carries only the store's own
 * identity — media + contact scalar fields, `code`, `template` and the
 * per-language descriptions. Language/currency are marketplace-wide (never
 * edited here). The root store is immutable and redirects to the core screen,
 * exactly like the v1 controller.
 *
 * updateStore() semantics are preserved: writes are gated by an allow-list of
 * store columns, and description edits are addressed per language. The title
 * text is stored in the `name` column (core 2.x schema — no `title` column).
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-root-admin-livewire
 * @aidlc-adr multi-vendor_admin-livewire-migration
 */
class VendorStoreConfigForm extends GP247AdminComponent
{
    /**
     * Permission label only; access is decided by URI+method (ADR-001 Layer-2).
     *
     * @var string|null
     */
    protected ?string $permission = 'admin_MultiVendor';

    /** @var int|string The configured vendor store id. */
    public $storeId;

    /** @var array<string, mixed> Editable scalar store fields. */
    public array $store = [];

    /** @var array<string, array<string, string>> Descriptions keyed by lang => field. */
    public array $desc = [];

    /**
     * Store commission override (%) as typed; '' = follow the marketplace rate.
     * Stored as a store-scoped admin_config row (CommissionPolicy), not a store column.
     */
    public string $commission = '';

    /** S3-4: id of the store's live plan subscription ('' = default plan). */
    public string $planId = '';

    /** Media (image path) fields rendered with the media picker. */
    private const MEDIA = ['logo', 'icon', 'og_image'];

    /** Store scalar text fields editable on this screen. */
    private const FIELDS = ['phone', 'long_phone', 'time_active', 'address', 'office', 'warehouse', 'email'];

    /**
     * Non-media store columns writable inline. Mirrors the v1
     * UPDATABLE_STORE_FIELDS, minus the fields this single-domain screen does
     * not expose (status is toggled on the list; language/currency follow the
     * marketplace; domain no longer exists).
     */
    private const IDENTITY = ['code', 'template'];

    /**
     * Per-language description fields. Column `name` holds the title text
     * (core 2.x renamed the legacy `title` column — getTitle() reads `name`).
     */
    private const DESC_FIELDS = ['name', 'keyword', 'description'];

    /**
     * Load the store, its descriptions and current field values, or redirect
     * when the id is the immutable root store / not found (same rules as v1).
     *
     * @param int|string $id
     * @return void
     */
    public function mount($id = null): void
    {
        parent::mount();

        // The root store is configured on the core screens, not here (v1 rule).
        if ((string) $id === (string) GP247_STORE_ID_ROOT) {
            $this->redirect(gp247_route_admin('admin_store.index'));

            return;
        }

        $model = AdminStore::with('descriptions')->find($id);
        if ($model === null) {
            $this->redirect(gp247_route_admin('admin_MultiVendor.index'));

            return;
        }

        $this->storeId = $id;
        $override = CommissionPolicy::overrideFor((string) $id);
        $this->commission = $override === null ? '' : (string) $override;
        $current = Plan::enabled() ? Plan::current((string) $id) : null;
        $this->planId = $current ? (string) $current->plan_id : '';

        foreach (array_merge(self::MEDIA, self::FIELDS, self::IDENTITY) as $field) {
            $this->store[$field] = (string) ($model->{$field} ?? '');
        }

        $descriptions = $model->descriptions->keyBy('lang');
        foreach (array_keys($this->languages()) as $code) {
            foreach (self::DESC_FIELDS as $field) {
                $this->desc[$code][$field] = (string) ($descriptions[$code][$field] ?? '');
            }
        }
    }

    /**
     * Active languages keyed by code for the description panel.
     *
     * @return array<string, mixed>
     */
    public function languages(): array
    {
        return AdminLanguage::getListActive()->all();
    }

    /**
     * Installed storefront templates keyed by folder name, or [] when gp247/front
     * is not installed at DB level (NFR-MAINT-001 — see VendorStoreCreateForm).
     *
     * @return array<string, mixed>
     */
    protected function templateOptions(): array
    {
        if (!function_exists('gp247_front_get_all_template_installed')
            || !Schema::connection(GP247_DB_CONNECTION)->hasTable(GP247_DB_PREFIX.'front_layout_block')
        ) {
            return [];
        }

        return (array) gp247_front_get_all_template_installed();
    }

    /**
     * Persist a scalar store field the moment it changes (Layer-2 gated). Only
     * allow-listed columns are writable — the wire path names the column, so the
     * allow-list is the boundary between this endpoint and an arbitrary update
     * (same guarantee as the v1 updateStore()). `code` is uniqueness-checked.
     *
     * @param mixed  $value
     * @param string $key   The changed `store.<key>` segment.
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function updatedStore($value, string $key): void
    {
        $this->authorizeAction('update');

        $allowed = array_merge(self::MEDIA, self::FIELDS, self::IDENTITY);
        if (!in_array($key, $allowed, true)) {
            return;
        }

        $clean = gp247_clean((string) $value);

        if ($key === 'code') {
            $taken = AdminStore::where('code', $clean)->where('id', '<>', $this->storeId)->exists();
            if ($taken || $clean === '') {
                $this->notify('error', gp247_language_render('multi_store.code_exist'));
                $this->store['code'] = (string) (AdminStore::where('id', $this->storeId)->value('code') ?? '');

                return;
            }
            AdminStore::where('id', $this->storeId)->update(['code' => $clean]);
        } else {
            AdminStore::where('id', $this->storeId)->update([$key => $clean]);
        }

        $this->notify('success', gp247_language_render('admin.msg_change_success'));
    }

    /**
     * Persist the store's commission override the moment it changes (Layer-2
     * gated). Empty clears the override (store follows the marketplace rate);
     * anything outside 0–100 is rejected and the field snaps back.
     *
     * @param mixed $value
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    /**
     * S3-4: the marketplace puts the store on a plan (new period, fee booked) or
     * takes it off ('' ⇒ back to the default plan).
     */
    public function updatedPlanId($value): void
    {
        $this->authorizeAction('update');
        $value = trim((string) $value);
        if ($value === '') {
            Plan::cancel((string) $this->storeId);
        } else {
            Plan::assign((string) $this->storeId, $value, function_exists('admin') && admin()->user() ? admin()->user()->id : null);
        }
        $current = Plan::current((string) $this->storeId);
        $this->planId = $current ? (string) $current->plan_id : '';
        $this->notify('success', gp247_language_render('admin.msg_change_success'));
    }

    public function updatedCommission($value): void
    {
        $this->authorizeAction('update');

        $raw = trim((string) $value);
        if ($raw === '') {
            CommissionPolicy::setOverride((string) $this->storeId, null);
            $this->commission = '';
            $this->notify('success', gp247_language_render('admin.msg_change_success'));

            return;
        }
        if (!is_numeric($raw) || (float) $raw < 0 || (float) $raw > 100) {
            $current = CommissionPolicy::overrideFor((string) $this->storeId);
            $this->commission = $current === null ? '' : (string) $current;
            $this->notify('error', gp247_language_render('multi_vendor.commission_invalid'));

            return;
        }

        CommissionPolicy::setOverride((string) $this->storeId, (int) $raw);
        $this->commission = (string) (int) $raw;
        $this->notify('success', gp247_language_render('admin.msg_change_success'));
    }

    /**
     * Persist a per-language description field. The wire path is
     * `desc.<lang>.<field>`, so $path arrives as "<lang>.<field>". Column `name`
     * holds the title text (renamed in core 2.x).
     *
     * @param mixed  $value
     * @param string $path
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function updatedDesc($value, string $path): void
    {
        $this->authorizeAction('update');

        [$lang, $field] = array_pad(explode('.', $path, 2), 2, '');
        $activeLangs = array_map('strval', array_keys($this->languages()));
        if ($lang === '' || !in_array($lang, $activeLangs, true) || !in_array($field, self::DESC_FIELDS, true)) {
            return;
        }

        AdminStore::updateDescription([
            'storeId' => $this->storeId,
            'lang'    => $lang,
            'name'    => $field,
            'value'   => gp247_clean((string) $value),
        ]);

        $this->notify('success', gp247_language_render('admin.msg_change_success'));
    }

    /**
     * @return View
     */
    public function render(): View
    {
        $plugin = new AppConfig;
        $languages = $this->languages();

        return view('Plugins/MultiVendor::Admin.screen.root.livewire.vendor_store_config', [
            'pathPlugin' => $plugin->appPath,
            'languages' => $languages,
            'mediaFields' => self::MEDIA,
            'fields' => self::FIELDS,
            'templateOptions' => $this->templateOptions(),
            'marketplaceRate' => CommissionPolicy::marketplaceRate(),
            'plans' => Plan::enabled() ? Plan::plans()->where('status', 1) : collect(),
            'planCurrent' => Plan::enabled() ? Plan::current((string) $this->storeId) : null,
            'planEffective' => Plan::planOf((string) $this->storeId),
            'canOverrideCommission' => \App\GP247\Plugins\MultiVendor\Tier\Tier::allows(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_PER_VENDOR_COMMISSION),
        ])->layout('gp247-admin::layouts.admin', [
            'title' => gp247_language_render('admin.store.config_store', ['id' => $this->storeId]),
        ]);
    }
}
