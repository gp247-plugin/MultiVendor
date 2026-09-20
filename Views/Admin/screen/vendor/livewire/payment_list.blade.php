{{--
    Vendor payment list (Livewire) — read-only payout ledger for the vendor's own
    store. Single full-width column: per-currency summary tiles above a paginated
    money-process table. Ports screen.vendor.payment_list (summary + grid) 1-to-1.
    Money is rendered exactly as the controller did (gp247_currency_render); the
    view never reformats amounts itself.

    @aidlc-unit multi-vendor-pro
    @aidlc-story US-multi-vendor-pro-vendor-admin-livewire
    @aidlc-adr multi-vendor_admin-livewire-migration
--}}
@php
    // TailAdmin status pills replacing the v1 AdminLTE `.badge-*` classes.
    $statusPill = [
        'done' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200',
        'processing' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200',
        'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-200',
        'canceled' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
    ];
    $th = 'px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';
    $td = 'px-3 py-2 text-gray-700 dark:text-gray-200';
@endphp

<div class="space-y-6" data-testid="multi-vendor-pro-payment-screen">

    <p class="text-sm text-gray-600 dark:text-gray-300" data-testid="multi-vendor-pro-payment-commission">
        <i class="fas fa-percent mr-1 text-gray-400"></i>{{ gp247_language_render('multi_vendor.commission_current', ['rate' => $commissionRate, 'share' => $vendorShare]) }}
    </p>

    {{-- Per-currency payout summary tiles --}}
    @foreach ($dataAmount as $currency => $row)
    <div>
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

    {{-- S1-5: where to pay me (nested component; account number write-only) --}}
    @if ($accountForm)
    @livewire($accountForm, [], key('payout-account'))
    @else
    @include('Plugins/MultiVendor::Admin.partials.pro_upsell', [
        'audience'     => \App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::VENDOR,
        'variant'      => 'block',
        'flag'         => \App\GP247\Plugins\MultiVendor\Tier\Tier::F_PAYOUT_ACCOUNT,
        'featureTitle' => gp247_language_render('multi_vendor.payout.title'),
        'featureDesc'  => gp247_language_render(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_PAYOUT_ACCOUNT)),
        'gatewayUrl'   => \App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::gatewayUrl(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::VENDOR, 'my-plan'),
    ])
    @endif

    {{-- Money-process ledger --}}
    <x-gp247::card>
        <x-slot:header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                    {{ gp247_language_render('multi_vendor.vendor_payment') }}
                </h3>
                @if ($canStatement && $exportUrl !== '')
                <form method="GET" action="{{ $exportUrl }}" target="_blank" class="flex flex-wrap items-end gap-2" data-testid="multi-vendor-pro-payment-statement-export">
                    <input type="date" name="from" class="rounded-lg border border-gray-300 bg-white px-2 py-1 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                    <input type="date" name="to" class="rounded-lg border border-gray-300 bg-white px-2 py-1 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                    <x-gp247::button type="submit" variant="secondary" size="sm" data-testid="multi-vendor-pro-payment-statement-submit">
                        <i class="fas fa-file-excel"></i> {{ gp247_language_render('multi_vendor.payout.export') }}
                    </x-gp247::button>
                </form>
                @else
                @include('Plugins/MultiVendor::Admin.partials.pro_upsell', [
                    'audience'     => \App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::VENDOR,
                    'variant'      => 'block',
                    'flag'         => \App\GP247\Plugins\MultiVendor\Tier\Tier::F_PAYOUT_STATEMENT,
                    'featureTitle' => gp247_language_render('multi_vendor.payout.export'),
                    'featureDesc'  => gp247_language_render(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_PAYOUT_STATEMENT)),
                    'gatewayUrl'   => \App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::gatewayUrl(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::VENDOR, 'my-plan'),
                ])
                @endif
            </div>
        </x-slot:header>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="{{ $th }}">{{ gp247_language_render('multi_vendor.payment.order_count') }}</th>
                        <th class="{{ $th }}">{{ gp247_language_render('multi_vendor.payment.total_sum') }}</th>
                        <th class="{{ $th }}">{{ gp247_language_render('multi_vendor.payment.amount') }}</th>
                        <th class="{{ $th }}">{{ gp247_language_render('multi_vendor.payment.content') }}</th>
                        <th class="{{ $th }}">{{ gp247_language_render('multi_vendor.payment.date_process') }}</th>
                        <th class="{{ $th }}">{{ gp247_language_render('multi_vendor.payment.date_pay') }}</th>
                        <th class="{{ $th }}">{{ gp247_language_render('multi_vendor.payout.column') }}</th>
                        <th class="{{ $th }}">{{ gp247_language_render('multi_vendor.payment.status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @forelse ($rows as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30" wire:key="pay-{{ $row->id }}" data-testid="multi-vendor-pro-payment-row">
                        <td class="{{ $td }}">x {{ number_format($row->order_count) }}</td>
                        <td class="{{ $td }}">{{ gp247_currency_render($row->total_sum, $row->currency) }}</td>
                        <td class="{{ $td }}">{{ gp247_currency_render($row->amount, $row->currency) }} (Rate: {{ $row->commission_rate }}%)</td>
                        <td class="{{ $td }}">{{ $row->content }}
                            @if (\App\GP247\Plugins\MultiVendor\Payout\Payout::isAdjustment($row->kind ?? null))
                                <span class="ml-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900 dark:text-amber-200" data-testid="multi-vendor-payment-kind">{{ \App\GP247\Plugins\MultiVendor\Payout\Payout::kindLabel($row->kind) }}</span>
                            @endif</td>
                        <td class="{{ $td }}">{{ $row->date_process }}</td>
                        <td class="{{ $td }}">{{ $row->date_pay }}</td>
                        <td class="{{ $td }} text-xs">{{ $row->payout_account ?: ($row->payout_method ?: '—') }}@if (!empty($row->payout_reference)) <span class="text-gray-400">#{{ $row->payout_reference }}</span>@endif</td>
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusPill[$row->status] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $row->status }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center">
                            <i class="fas fa-box-open mb-3 block text-3xl text-gray-300 dark:text-gray-600"></i>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.no_data') }}</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $rows->links() }}</div>
    </x-gp247::card>
</div>
