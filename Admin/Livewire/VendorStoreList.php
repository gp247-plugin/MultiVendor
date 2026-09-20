<?php
#App\GP247\Plugins\MultiVendor\Admin\Livewire\VendorStoreList.php

namespace App\GP247\Plugins\MultiVendor\Admin\Livewire;

use App\GP247\Plugins\MultiVendor\AppConfig;
use App\GP247\Plugins\MultiVendor\Events\DeletedVendorStore;
use App\GP247\Plugins\MultiVendor\Events\DeletingVendorStore;
use GP247\Core\AdminShell\Infrastructure\GP247AdminComponent;
use GP247\Core\Models\AdminStore;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vendor store list screen (v2 Livewire port of the legacy
 * AdminRootVendorStoreController@index + its inline `status` writer and the
 * ajax delete endpoint). Mirrors the MultiStore StoreListManager: one row per
 * store with the marketplace shop link, an inline open/close (`status`) toggle
 * for every sub-store and Configure / Delete actions. The root store is never
 * toggled or deleted.
 *
 * Differences from MultiStore (single-domain marketplace decisions, mod
 * 20260908T201252): no `domain` column, no per-store website link, and the
 * inline toggle drives the marketplace `status` flag (open/close a booth)
 * instead of MultiStore's owner `active` flag. Route names are unchanged
 * (`admin_MultiVendor.index` / `.create` / `.config`).
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-root-admin-livewire
 * @aidlc-adr multi-vendor_admin-livewire-migration
 */
class VendorStoreList extends GP247AdminComponent
{
    /**
     * Permission label only; the access decision is by URI+method (ADR-001
     * Layer-2). Kept so the resolver can name the screen deterministically.
     *
     * @var string|null
     */
    protected ?string $permission = 'admin_MultiVendor';

    /** @var array<int|string, bool> Open/close (`status`) flag per store id. */
    public array $status = [];

    /**
     * Load the per-store open/close toggle state.
     *
     * @return void
     */
    public function mount(): void
    {
        parent::mount();

        foreach (AdminStore::pluck('status', 'id') as $id => $flag) {
            $this->status[$id] = (bool) (int) $flag;
        }
    }

    /**
     * Persist a store's open/close (`status`) toggle inline (Layer-2 gated).
     * The root store cannot be closed from this screen — same rule as the v1
     * controller, which skipped the checkbox for GP247_STORE_ID_ROOT. `status`
     * is the only column this action may touch (allow-list of one).
     *
     * @param mixed  $value
     * @param string $key   The changed store id (wire path `status.<id>`).
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function updatedStatus($value, string $key): void
    {
        $this->authorizeAction('update');

        if ((string) $key === (string) GP247_STORE_ID_ROOT) {
            $this->status[$key] = true;

            return;
        }

        $wasOpen = (int) AdminStore::where('id', $key)->value('status') === 1;
        AdminStore::where('id', $key)->update(['status' => $value ? 1 : 0]);
        // E3 (S1-2): opening a pending booth is the approval moment — tell the vendor.
        if ($value && !$wasOpen) {
            \App\GP247\Plugins\MultiVendor\Notifications\VendorNotifier::vendorApproved((string) $key);
        }
        $this->notify('success', gp247_language_render('admin.msg_change_success'));
    }

    /**
     * Delete a vendor store (v2 port of the controller's ajax delete). Refuses
     * the root store and the store the admin session is currently working in
     * (config('app.storeId')), exactly like v1. The store-scoped front rows
     * (menu links + layout blocks) are purged, then AdminStore::destroy() fires
     * the model `deleting` event that removes this store's descriptions and
     * admin_config rows. The whole cascade runs in one transaction so a partial
     * failure never leaves orphan pivot rows. Confirmed client-side.
     *
     * @param int|string|null $id Store id to delete.
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function delete($id): void
    {
        $this->authorizeAction('delete');

        if ($id === null || (string) $id === (string) GP247_STORE_ID_ROOT) {
            return;
        }
        if ((string) config('app.storeId') === (string) $id) {
            $this->notify('error', gp247_language_render('store.cannot_delete'));

            return;
        }

        $store = AdminStore::find($id);
        if ($store === null) {
            return;
        }

        // WHY: keep the same before/after signals the v1 controller emitted so
        // any listener (marketplace cleanup, vendor unlink) still fires.
        DeletingVendorStore::dispatch($store);

        try {
            DB::connection(GP247_DB_CONNECTION)->transaction(function () use ($id) {
                $this->purgeStoreScopedData((string) $id);
                // AdminStore::boot() deletes the description rows and the
                // admin_config rows in its `deleting` event, so they are not
                // repeated here — destroy() fires model events.
                AdminStore::destroy($id);
            });
        } catch (\Throwable $e) {
            gp247_report('[MultiVendor] delete store '.$id.' failed: '.$e->getMessage());
            $this->notify('error', $e->getMessage());

            return;
        }

        DeletedVendorStore::dispatch($store);

        unset($this->status[$id]);
        $this->notify('success', gp247_language_render('action.delete_confirm_deleted_msg'));
    }

    /**
     * Remove the store-scoped front rows the v1 controller deleted by hand:
     * this store's menu links (parent row is store-owned, so both the pivot and
     * the front_link parent go) and its layout blocks. Must run inside the
     * caller's transaction.
     *
     * WHY the table checks: gp247/front is not a composer requirement of this
     * plugin, so its tables may be absent. Going through the query builder with
     * a Schema::hasTable() guard keeps this method from fataling on a class that
     * was never autoloadable (NFR-MAINT-001) — the v1 controller referenced the
     * GP247\Front models unconditionally.
     *
     * @param string $id Store id.
     * @return void
     */
    private function purgeStoreScopedData(string $id): void
    {
        $linkPivot = $this->scopedQuery('front_link_store', $id);
        if ($linkPivot !== null) {
            $linkIds = $linkPivot->pluck('link_id')->all();
            $this->scopedQuery('front_link_store', $id)->delete();

            $linkTable = GP247_DB_PREFIX.'front_link';
            if ($linkIds !== [] && Schema::connection(GP247_DB_CONNECTION)->hasTable($linkTable)) {
                DB::connection(GP247_DB_CONNECTION)
                    ->table($linkTable)
                    ->whereIn('id', $linkIds)
                    ->delete();
            }
        }

        $this->scopedQuery('front_layout_block', $id)?->delete();
    }

    /**
     * Query builder scoped to one store, or null when the table is absent.
     *
     * @param string $table Table name without the GP247 prefix.
     * @param string $id    Store id.
     * @return Builder|null
     */
    private function scopedQuery(string $table, string $id): ?Builder
    {
        if (!Schema::connection(GP247_DB_CONNECTION)->hasTable(GP247_DB_PREFIX.$table)) {
            return null;
        }

        return DB::connection(GP247_DB_CONNECTION)
            ->table(GP247_DB_PREFIX.$table)
            ->where('store_id', $id);
    }

    /**
     * @return View
     */
    public function render(): View
    {
        $plugin = new AppConfig;

        $stories = AdminStore::with('descriptions')->get()->keyBy('id');
        // S1-1: effective commission per store (override or marketplace) for the read-only column.
        $commissions = [];
        foreach ($stories as $id => $store) {
            $commissions[$id] = [
                'rate' => \App\GP247\Plugins\MultiVendor\Commission\CommissionPolicy::rateFor((string) $id),
                'overridden' => \App\GP247\Plugins\MultiVendor\Commission\CommissionPolicy::isOverridden((string) $id),
            ];
        }

        // S3-2: verified badge (Pro) per store.
        $verified = array_fill_keys(\App\GP247\Plugins\MultiVendor\Kyc\Kyc::verifiedIds(array_map('strval', $stories->keys()->all())), true);
        // S3-4: effective plan name per store (Pro).
        $plans = [];
        if (\App\GP247\Plugins\MultiVendor\Plans\Plan::enabled()) {
            foreach ($stories as $id => $store) {
                $plan = \App\GP247\Plugins\MultiVendor\Plans\Plan::planOf((string) $id);
                $plans[$id] = $plan ? (string) $plan->name : '';
            }
        }

        return view('Plugins/MultiVendor::Admin.screen.root.livewire.vendor_store_list', [
            'stories' => $stories,
            'commissions' => $commissions,
            'verified' => $verified,
            'plans' => $plans,
            'pathPlugin' => $plugin->appPath,
        ])->layout('gp247-admin::layouts.admin', [
            'title' => gp247_language_render('admin.store.list'),
        ]);
    }
}
