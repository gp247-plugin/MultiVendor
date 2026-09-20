{{--
    Vendor dashboard (Livewire) — read-only overview for the vendor's own store.
    Ports screen.vendor.dashboard 1-to-1: two stat cards over the 30-day order /
    amount charts and the 12-month amount chart. TailAdmin <x-gp247::*> widgets +
    Alpine + ApexCharts, no jQuery.

    Charts under Livewire: the Alpine factory gp247DashboardChart(config) (core
    admin.js) renders on its init() lifecycle. wire:key hashes the series so a
    Livewire DOM morph replaces the node and Alpine re-initialises the chart with
    fresh data — the same idiom the ported root vendor_report screen uses.

    @aidlc-unit multi-vendor-pro
    @aidlc-story US-multi-vendor-pro-vendor-admin-livewire
    @aidlc-adr multi-vendor_admin-livewire-migration

    Variables: $totalOrder, $totalProduct, $orderInMonth, $amountInMonth, $dataInYear.
--}}
@push('scripts')
    {{-- ApexCharts ships with the core admin shell (it drives the staff
         dashboard). v1 loaded Highcharts from GP247/Core/plugin/, a folder core
         2.x no longer publishes. --}}
    <script src="{{ gp247_file('GP247/Core/AdminShell/vendor/apexcharts/apexcharts.min.js') }}"></script>
@endpush

<div class="space-y-5" data-testid="multi-vendor-pro-dashboard-screen">
    @if (!empty($kycNotice) && \Illuminate\Support\Facades\Route::has('vendor_admin_kyc.index'))
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200" data-testid="multi-vendor-kyc-notice">
            <span><i class="fas fa-id-card mr-1"></i>{{ gp247_language_render($kycNotice === 'pending' ? 'multi_vendor.kyc.notice_pending' : 'multi_vendor.kyc.notice_required') }}</span>
            @if ($kycNotice !== 'pending')
            <a href="{{ gp247_route_admin('vendor_admin_kyc.index') }}" class="font-semibold underline">{{ gp247_language_render('multi_vendor.kyc.notice_action') }}</a>
            @endif
        </div>
    @endif

    @if (!empty($planInfo))
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-200" data-testid="multi-vendor-plan-card">
            <span>
                <i class="fas fa-layer-group mr-1"></i><strong>{{ $planInfo['name'] }}</strong>
                · {{ gp247_language_render('multi_vendor.plan.products_used', ['count' => $planInfo['count'], 'max' => $planInfo['max'] === null ? gp247_language_render('multi_vendor.plan.unlimited') : $planInfo['max']]) }}
                @if ($planInfo['fee'] !== '') · {{ $planInfo['fee'] }} @endif
            </span>
            <span class="text-xs">
                @if ($planInfo['period_end'])
                    {{ gp247_language_render('multi_vendor.plan.valid_until', ['date' => $planInfo['period_end']]) }}
                @elseif ($planInfo['expired'])
                    {{ gp247_language_render('multi_vendor.plan.expired_notice') }}
                @endif
                @if ($planInfo['max'] !== null && $planInfo['count'] >= $planInfo['max'])
                    <span class="ml-2 font-semibold text-amber-700 dark:text-amber-300">{{ gp247_language_render('multi_vendor.plan.limit_reached', ['max' => $planInfo['max']]) }}</span>
                @endif
            </span>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-gp247::stat-card color="emerald" icon="fas fa-shopping-cart"
            :label="gp247_language_render('admin.dashboard.total_order')"
            :value="number_format($totalOrder)"
            :url="gp247_route_admin('vendor_admin_order.index')"
            data-testid="multi-vendor-pro-dashboard-total-order" />

        <x-gp247::stat-card color="sky" icon="fas fa-tags"
            :label="gp247_language_render('admin.dashboard.total_product')"
            :value="number_format($totalProduct).(!empty($planInfo) && $planInfo['max'] !== null ? ' / '.number_format($planInfo['max']) : '')"
            :url="gp247_route_admin('vendor_admin_product.index')"
            data-testid="multi-vendor-pro-dashboard-total-product" />
    </div>

    {{-- This month: order count and order value. v1 drew both as two series on
         one Highcharts chart; the shell's shared chart factory renders a single
         series, so they are two charts rather than a bespoke one-off. --}}
    <x-gp247::card :title="gp247_language_render('admin.dashboard.order_month')">
        <div id="chart-month" class="w-full"
            data-testid="multi-vendor-pro-dashboard-chart-month"
            wire:key="chart-month-{{ md5(json_encode($orderInMonth)) }}"
            x-data="gp247DashboardChart({
                labels: @js(array_keys($orderInMonth)),
                values: @js(array_values($orderInMonth)),
                name: @js(gp247_language_render('admin.dashboard.order')),
            })"></div>

        <div id="chart-month-amount" class="mt-4 w-full"
            data-testid="multi-vendor-pro-dashboard-chart-month-amount"
            wire:key="chart-month-amount-{{ md5(json_encode($amountInMonth)) }}"
            x-data="gp247DashboardChart({
                labels: @js(array_keys($amountInMonth)),
                values: @js(array_values($amountInMonth)),
                name: @js(gp247_language_render('admin.dashboard.amount')),
                color: '#f59e0b',
            })"></div>
    </x-gp247::card>

    <x-gp247::card :title="gp247_language_render('admin.dashboard.order_year')">
        <div id="chart-year" class="w-full"
            data-testid="multi-vendor-pro-dashboard-chart-year"
            wire:key="chart-year-{{ md5(json_encode($dataInYear)) }}"
            x-data="gp247DashboardChart({
                labels: @js(array_keys($dataInYear)),
                values: @js(array_values($dataInYear)),
                name: @js(gp247_language_render('admin.dashboard.amount')),
                color: '#10b981',
            })"></div>
    </x-gp247::card>

</div>
