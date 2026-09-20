{{--
    Per-currency payout summary tiles shown above the payment lists.

    Replaces the v1 AdminLTE `.info-box` trio with the shared TailAdmin KPI tile.

    @param array $dataAmount currency => [sumAmount, sumAmountDone, sumAmountRemaining]
--}}
@foreach ($dataAmount as $currency => $row)
<div class="mb-5">
    <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $currency }}</h2>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-gp247::stat-card color="sky" icon="far fa-money-bill-alt"
            :label="gp247_language_render('multi_vendor.payment.life_time')"
            :value="gp247_currency_render($row['sumAmount'] ?? 0, $currency)" />

        <x-gp247::stat-card color="emerald" icon="far fa-money-bill-alt"
            :label="gp247_language_render('multi_vendor.payment.payout')"
            :value="gp247_currency_render($row['sumAmountDone'] ?? 0, $currency)" />

        <x-gp247::stat-card color="amber" icon="far fa-money-bill-alt"
            :label="gp247_language_render('multi_vendor.payment.remaining')"
            :value="gp247_currency_render($row['sumAmountRemaining'] ?? 0, $currency)" />
    </div>
</div>
@endforeach
