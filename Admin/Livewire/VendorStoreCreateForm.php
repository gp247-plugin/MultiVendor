<?php
#App\GP247\Plugins\MultiVendor\Admin\Livewire\VendorStoreCreateForm.php

namespace App\GP247\Plugins\MultiVendor\Admin\Livewire;

use App\GP247\Plugins\MultiVendor\AppConfig;
use App\GP247\Plugins\MultiVendor\Events\CreatedVendorStore;
use App\GP247\Plugins\MultiVendor\Events\CreatingVendorStore;
use GP247\Core\AdminShell\Infrastructure\GP247AdminComponent;
use GP247\Core\AdminShell\Infrastructure\HasValidationLabels;
use GP247\Core\Models\AdminLanguage;
use GP247\Core\Models\AdminStore;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Add new vendor store" form (v2 Livewire port of
 * AdminRootVendorStoreController@create + @postCreate). Same transactional
 * create as v1: insert the store, its per-language descriptions and seed the
 * per-store default data via AdminStore::setUpDataDefault(), all inside one
 * transaction, wrapped by the CreatingVendorStore / CreatedVendorStore events.
 *
 * Single-domain marketplace decisions (mod 20260908T201252) — kept identical to
 * the v1 create screen, and different from MultiStore:
 *  - no `domain` field (partner domains were removed);
 *  - no language/currency selects — a vendor booth follows the marketplace, so
 *    both columns are stored empty and inherited at runtime;
 *  - no maintenance editor; a default maintenance page is seeded per language
 *    exactly as v1 did;
 *  - a `status` toggle is present (open/close the booth) — MultiStore dropped it.
 *
 * The description title text is written to the `name` column: core 2.x's
 * admin_store_description has no `title` column (getTitle() reads `name`), so
 * this mirrors MultiStore and the actual schema — see the report's review notes.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-root-admin-livewire
 * @aidlc-adr multi-vendor_admin-livewire-migration
 */
class VendorStoreCreateForm extends GP247AdminComponent
{
    use HasValidationLabels;

    /**
     * Permission label only; access is decided by URI+method (ADR-001 Layer-2).
     *
     * @var string|null
     */
    protected ?string $permission = 'admin_MultiVendor';

    /** @var array<string, array<string, string>> Descriptions keyed by lang => field. */
    public array $descriptions = [];

    /** @var array<string, mixed> Scalar store fields (no domain/language/currency). */
    public array $store = [
        'logo' => '',
        'phone' => '',
        'long_phone' => '',
        'email' => '',
        'time_active' => '',
        'address' => '',
        'office' => '',
        'warehouse' => '',
        'code' => '',
        'template' => '',
        'status' => false,
    ];

    /**
     * Default maintenance-page copy seeded into every language (the same HTML
     * the v1 postCreate() hardcoded). Not editable on this screen — a vendor
     * booth has no maintenance editor — but stored so the store is complete.
     */
    private const DEFAULT_MAINTAIN_CONTENT = '<center><img src="/images/maintenance.png" />
                            <h3><span style="color:#e74c3c;"><strong>Sorry! We are currently doing site maintenance!</strong></span></h3>
                            </center>';

    /**
     * Seed empty description state per active language.
     *
     * @return void
     */
    public function mount(): void
    {
        parent::mount();

        foreach (array_keys($this->languages()) as $code) {
            $this->descriptions[$code] = [
                'title' => '',
                'keyword' => '',
                'description' => '',
            ];
        }
    }

    /**
     * Active languages keyed by code.
     *
     * @return array<string, mixed>
     */
    public function languages(): array
    {
        return AdminLanguage::getListActive()->all();
    }

    /**
     * Installed storefront templates keyed by folder name, or [] when gp247/front
     * is not installed at DB level. Inlined from the core store_info screen
     * (WebsiteInfo::frontTemplateAvailable): gp247/front classes/helpers are
     * always autoloadable as composer deps, so the front_layout_block table is
     * the only reliable install signal (NFR-MAINT-001).
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
     * Validation rules — same shape as the v1 postCreate() (no domain rule).
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'descriptions.*.title' => 'required|string|max:200',
            'descriptions.*.keyword' => 'nullable|string|max:200',
            'descriptions.*.description' => 'nullable|string|max:300',
            'store.code' => 'required|string|max:20|unique:"'.AdminStore::class.'",code',
            'store.template' => 'required',
        ];
    }

    /**
     * Localize the rule keys so validation errors read the field name instead of
     * the raw Livewire state path.
     *
     * @return array<string, string>
     */
    protected function attributeLabels(): array
    {
        return [
            'descriptions.*.title' => 'store.title',
            'descriptions.*.keyword' => 'store.keyword',
            'descriptions.*.description' => 'store.description',
            'store.code' => 'admin.store.code',
            'store.template' => 'admin.store.template',
        ];
    }

    /**
     * Create the vendor store: normalize the code, validate, then insert the
     * store + descriptions and seed default per-store data inside one
     * transaction bracketed by the marketplace events; finally return to the
     * list with a success flash.
     *
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function save(): void
    {
        $this->authorizeAction('create');

        // S4-1: Free edition vendor quota (server-side, before validation).
        if (\App\GP247\Plugins\MultiVendor\Tier\Tier::quotaReached()) {
            $this->notify('error', gp247_language_render('multi_vendor.tier.quota_reached', ['quota' => \App\GP247\Plugins\MultiVendor\Tier\Tier::FREE_VENDOR_QUOTA]));

            return;
        }

        // WHY: the code becomes the marketplace path segment (/vendor/{code}),
        // so it is url-formatted and capped at 20 chars before the unique check,
        // exactly as the v1 controller did.
        $this->store['code'] = gp247_word_limit(gp247_word_format_url((string) $this->store['code']), 20);
        $this->validate();

        $dataInsert = [
            'logo'        => $this->store['logo'],
            'phone'       => $this->store['phone'],
            'long_phone'  => $this->store['long_phone'],
            'email'       => $this->store['email'],
            'time_active' => $this->store['time_active'],
            'address'     => $this->store['address'],
            'office'      => $this->store['office'],
            'warehouse'   => $this->store['warehouse'],
            // WHY empty language/currency: a vendor booth inherits both from the
            // marketplace at runtime (single-domain marketplace decision).
            'language'    => '',
            'currency'    => '',
            'template'    => $this->store['template'],
            'code'        => $this->store['code'],
            'status'      => empty($this->store['status']) ? 0 : 1,
        ];

        // Keep the v1 "before create" signal (marketplace listeners depend on it).
        CreatingVendorStore::dispatch($dataInsert);

        try {
            DB::connection(GP247_DB_CONNECTION)
                ->transaction(function () use ($dataInsert) {
                    $store = AdminStore::create($dataInsert);
                    $dataDes = [];
                    foreach (array_keys($this->languages()) as $code) {
                        $dataDes[] = [
                            'store_id'    => $store->id,
                            'lang'        => $code,
                            // WHY: core 2.x's admin_store_description has no `title`
                            // column — the title text lives in `name` (getTitle()).
                            'name'        => $this->descriptions[$code]['title'] ?? '',
                            'keyword'     => $this->descriptions[$code]['keyword'] ?? '',
                            'description' => $this->descriptions[$code]['description'] ?? '',
                            'maintain_content' => self::DEFAULT_MAINTAIN_CONTENT,
                        ];
                    }
                    AdminStore::insertDescription($dataDes);

                    // Seed the default config/layout data for the new store.
                    AdminStore::setUpDataDefault($store);
                }, 2);
        } catch (\Throwable $e) {
            $this->notify('error', $e->getMessage());

            return;
        }

        // Keep the v1 "after create" signal, resolving the freshly-created store.
        CreatedVendorStore::dispatch(AdminStore::where('code', $dataInsert['code'])->first());

        session()->flash('gp247_admin_success', gp247_language_render('action.create_success'));
        $this->redirect(gp247_route_admin('admin_MultiVendor.index'));
    }

    /**
     * @return View
     */
    public function render(): View
    {
        $plugin = new AppConfig;

        return view('Plugins/MultiVendor::Admin.screen.root.livewire.vendor_store_add', [
            'pathPlugin' => $plugin->appPath,
            'languages' => $this->languages(),
            'templateOptions' => $this->templateOptions(),
        ])->layout('gp247-admin::layouts.admin', [
            'title' => gp247_language_render('admin.store.add_new_title'),
        ]);
    }
}
