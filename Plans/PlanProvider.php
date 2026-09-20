<?php

namespace App\GP247\Plugins\MultiVendor\Plans;

use Illuminate\Support\Collection;

/**
 * What the marketplace needs to know about vendor plans without owning them.
 *
 * Plans (product cap, own commission rate, periodic fee) are a paid feature,
 * but four free screens have a slot for them: the store config form (put a
 * store on a plan), the store list (plan badge), the vendor dashboard (plan
 * card) and the vendor product form (cap reached). The commission policy also
 * asks for a plan's rate. All of that goes through this contract; the plans
 * themselves — tables, rules, fee booking, the two screens — ship with the paid
 * package, which registers an implementation under
 * `config('Plugins/MultiVendor.plans.provider')`. Without one, NullPlanProvider
 * answers "no plans on this marketplace".
 *
 * Plan and subscription rows are `object`: the free plugin reads attributes
 * (name, plan_id, period dates, fee) and never names the paid models.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-paid-code-location
 * @aidlc-adr multi-vendor_pro-code-relocation
 */
interface PlanProvider
{
    /** Whether this marketplace sells plans at all. */
    public function enabled(): bool;

    /** Every plan the marketplace defined, default first (objects with id, name, fee_amount, fee_currency, fee_period, status). */
    public function plans(): Collection;

    /** The store's live subscription, or null. */
    public function current(string $storeId): ?object;

    /** The plan that applies to the store (its subscription's, else the default), or null. */
    public function planOf(string $storeId): ?object;

    /** Put the store on a plan: a new period opens and the fee is booked. */
    public function assign(string $storeId, string $planId, $adminId = null, string $note = ''): ?object;

    /** Take the store off its plan. Returns how many periods were closed. */
    public function cancel(string $storeId): int;

    /**
     * What the vendor dashboard shows, or null when no plan applies.
     *
     * @return array{name:string, max:?int, count:int, period_end:?string, fee:string, expired:bool}|null
     */
    public function summary(string $storeId): ?array;

    /** Product cap of the store's plan; null = unlimited. */
    public function maxProducts(string $storeId): ?int;

    /** Whether the store already lists as many products as its plan allows. */
    public function productLimitReached(string $storeId): bool;

    /** The plan's own commission rate, or null when the plan does not set one. */
    public function rateFor(string $storeId): ?int;
}
