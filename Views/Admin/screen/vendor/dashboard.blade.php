@extends($templatePathAdminVendor.'layout')

@push('scripts')
    {{-- ApexCharts ships with the core admin shell (it drives the staff
         dashboard). v1 loaded Highcharts from GP247/Core/plugin/, a folder core
         2.x no longer publishes. --}}
    <script src="{{ gp247_file('GP247/Core/AdminShell/vendor/apexcharts/apexcharts.min.js') }}"></script>
@endpush

@section('main')

<div class="space-y-5">

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-gp247::stat-card color="emerald" icon="fas fa-shopping-cart"
            :label="gp247_language_render('admin.dashboard.total_order')"
            :value="number_format($totalOrder)"
            :url="gp247_route_admin('vendor_admin_order.index')" />

        <x-gp247::stat-card color="sky" icon="fas fa-tags"
            :label="gp247_language_render('admin.dashboard.total_product')"
            :value="number_format($totalProduct)"
            :url="gp247_route_admin('vendor_admin_product.index')" />
    </div>

    {{-- This month: order count and order value. v1 drew both as two series on
         one Highcharts chart; the shell's shared chart factory renders a single
         series, so they are two charts rather than a bespoke one-off. --}}
    <x-gp247::card :title="gp247_language_render('admin.dashboard.order_month')">
        <div id="chart-month" class="w-full"
            x-data="gp247DashboardChart({
                labels: @js(array_keys($orderInMonth)),
                values: @js(array_values($orderInMonth)),
                name: @js(gp247_language_render('admin.dashboard.order')),
            })"></div>

        <div id="chart-month-amount" class="mt-4 w-full"
            x-data="gp247DashboardChart({
                labels: @js(array_keys($amountInMonth)),
                values: @js(array_values($amountInMonth)),
                name: @js(gp247_language_render('admin.dashboard.amount')),
                color: '#f59e0b',
            })"></div>
    </x-gp247::card>

    <x-gp247::card :title="gp247_language_render('admin.dashboard.order_year')">
        <div id="chart-year" class="w-full"
            x-data="gp247DashboardChart({
                labels: @js(array_keys($dataInYear)),
                values: @js(array_values($dataInYear)),
                name: @js(gp247_language_render('admin.dashboard.amount')),
                color: '#10b981',
            })"></div>
    </x-gp247::card>

</div>

@endsection
