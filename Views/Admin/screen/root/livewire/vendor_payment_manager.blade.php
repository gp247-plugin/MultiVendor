{{--
    Root vendor payout manager (Livewire) — v2 port of root/payment_list +
    root/payment_add. One component, two modes: the list (with the per-currency
    summary and the "run payout" form) when $editId is null, otherwise the edit
    form. Money logic lives in the component (process/save); this view is markup
    only. TailAdmin + Alpine, no jQuery.

    @aidlc-unit multi-vendor-pro
    @aidlc-story US-multi-vendor-pro-root-admin-livewire
    @aidlc-adr multi-vendor_admin-livewire-migration

    Variables: $payments, $dataAmount, $mapcolor, $currencyOptions, $statusOptions.
--}}
@php
    $input = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
    $label = 'mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200';
    $errorCls = 'mt-1 text-xs text-red-500';
@endphp

<div>
@if ($editId !== null)
    {{-- ============================ EDIT MODE ============================ --}}
    <div class="space-y-5">
        <div class="flex justify-end">
            <x-gp247::button href="{{ gp247_route_admin('admin_MultiVendorPayment.index') }}" variant="secondary" size="sm">
                <i class="fa fa-list"></i> {{ gp247_language_render('admin.back_list') }}
            </x-gp247::button>
        </div>

        <form wire:submit="save">
            <x-gp247::card>
                <div class="space-y-4">
                    <div>
                        <label for="edit_content" class="{{ $label }}">{{ gp247_language_render('multi_vendor.payment.content') }}</label>
                        <input type="text" id="edit_content" class="{{ $input }}" wire:model="form.content"
                            data-testid="multi-vendor-pro-payment-edit-content">
                        @error('form.content')<p class="{{ $errorCls }}">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="edit_comment" class="{{ $label }}">{{ gp247_language_render('multi_vendor.payment.comment') }}</label>
                        <input type="text" id="edit_comment" class="{{ $input }}" wire:model="form.comment"
                            data-testid="multi-vendor-pro-payment-edit-comment">
                        @error('form.comment')<p class="{{ $errorCls }}">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="edit_total_sum" class="{{ $label }}">{{ gp247_language_render('multi_vendor.payment.total_sum') }}</label>
                        <input type="number" id="edit_total_sum" class="{{ $input }}" wire:model="form.total_sum"
                            data-testid="multi-vendor-pro-payment-edit-total-sum">
                        @error('form.total_sum')<p class="{{ $errorCls }}">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="edit_order_count" class="{{ $label }}">{{ gp247_language_render('multi_vendor.payment.order_count') }}</label>
                        <input type="number" id="edit_order_count" class="{{ $input }}" wire:model="form.order_count"
                            data-testid="multi-vendor-pro-payment-edit-order-count">
                        @error('form.order_count')<p class="{{ $errorCls }}">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="edit_amount" class="{{ $label }}">{{ gp247_language_render('multi_vendor.payment.amount') }}</label>
                        <input type="number" id="edit_amount" class="{{ $input }}" wire:model="form.amount"
                            data-testid="multi-vendor-pro-payment-edit-amount">
                        @error('form.amount')<p class="{{ $errorCls }}">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="edit_currency" class="{{ $label }}">{{ gp247_language_render('multi_vendor.payment.currency') }}</label>
                        <select id="edit_currency" class="{{ $input }}" wire:model="form.currency"
                            data-testid="multi-vendor-pro-payment-edit-currency">
                            <option value=""></option>
                            @foreach ($currencyOptions as $opt)
                                <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                        @error('form.currency')<p class="{{ $errorCls }}">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="edit_date_process" class="{{ $label }}">{{ gp247_language_render('multi_vendor.payment.date_process') }}</label>
                        <input type="date" id="edit_date_process" class="{{ $input }}" wire:model="form.date_process"
                            data-testid="multi-vendor-pro-payment-edit-date-process">
                        @error('form.date_process')<p class="{{ $errorCls }}">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="edit_payout_reference" class="{{ $label }}">{{ gp247_language_render('multi_vendor.payout.reference') }}</label>
                        <input type="text" id="edit_payout_reference" class="{{ $input }}" wire:model="form.payout_reference" maxlength="150"
                            data-testid="multi-vendor-pro-payment-edit-reference">
                        @error('form.payout_reference')<p class="{{ $errorCls }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="edit_status" class="{{ $label }}">{{ gp247_language_render('multi_vendor.payment.status') }}</label>
                        <select id="edit_status" class="{{ $input }}" wire:model="form.status"
                            data-testid="multi-vendor-pro-payment-edit-status">
                            @foreach ($statusOptions as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('form.status')<p class="{{ $errorCls }}">{{ $message }}</p>@enderror
                    </div>
                </div>

                <x-slot:footer>
                    <div class="flex items-center justify-end">
                        <x-gp247::button type="submit" variant="primary" wire:loading.attr="disabled"
                            data-testid="multi-vendor-pro-payment-edit-submit">
                            {{ gp247_language_render('action.submit') }}
                        </x-gp247::button>
                    </div>
                </x-slot:footer>
            </x-gp247::card>
        </form>
    </div>
@else
    {{-- ============================ LIST MODE ============================ --}}
    <div class="space-y-5">

        {{-- Per-currency payout summary (life-time / paid / remaining). --}}
        @if (count($dataAmount))
            @foreach ($dataAmount as $currency => $row)
            <div class="mb-1">
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
        @endif

        {{-- Run a payout for a given processing date (the process() action). --}}
        <x-gp247::card>
            <form wire:submit="process" class="flex flex-wrap items-end gap-4">
                <div class="w-full sm:w-64">
                    <label for="process_date" class="{{ $label }}">{{ gp247_language_render('multi_vendor.vendor_payment_date') }}</label>
                    <input type="date" id="process_date" class="{{ $input }}" wire:model="processDate" required
                        data-testid="multi-vendor-pro-payment-process-date">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ strip_tags(gp247_language_render('multi_vendor.vendor_payment_date_help')) }}</p>
                </div>
                <x-gp247::button type="submit" variant="primary" wire:loading.attr="disabled"
                    data-testid="multi-vendor-pro-payment-process-submit">
                    {{ gp247_language_render('multi_vendor.vendor_payment_button') }}
                </x-gp247::button>
            </form>
        </x-gp247::card>

        {{-- Money-process list. --}}

        {{-- S5-2: pay a whole batch. A batch is one payout period and one
             currency, which is exactly what a bank transfer file is. --}}
        @if ($batchPanel)
        @livewire($batchPanel, [], key('payout-batch'))
        @else
        <div class="mb-4">
            @include('Plugins/MultiVendor::Admin.partials.pro_upsell', [
                'audience'     => \App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::ROOT,
                'variant'      => 'block',
                'flag'         => \App\GP247\Plugins\MultiVendor\Tier\Tier::F_PAYOUT_BATCH,
                'featureTitle' => gp247_language_render('multi_vendor.batch.title'),
                'featureDesc'  => gp247_language_render(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_PAYOUT_BATCH)),
                'gatewayUrl'   => \App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::gatewayUrl(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::ROOT, 'report'),
            ])
        </div>
        @endif

        <x-gp247::card>
            <x-slot:header>
                <div class="flex w-full max-w-sm items-center">
                    <input type="text" wire:model.live.debounce.400ms="keyword"
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        placeholder="{{ gp247_language_render('front.store_list') }}"
                        data-testid="multi-vendor-pro-payment-search">
                </div>
            </x-slot:header>

            <div class="overflow-x-auto">
                @if ($canStatement)
                <form method="POST" action="{{ gp247_route_admin('admin_MultiVendorPayment.export') }}" target="_blank" class="mb-3 flex flex-wrap items-end gap-2" data-testid="multi-vendor-pro-payment-export">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ gp247_language_render('multi_vendor.store') }}</label>
                        <input type="text" name="store_id" value="{{ $keyword }}" class="{{ $input }} w-40" placeholder="{{ gp247_language_render('multi_vendor.store_empty') }}">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ gp247_language_render('multi_vendor.payout.from') }}</label>
                        <input type="date" name="from" class="{{ $input }} w-40">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ gp247_language_render('multi_vendor.payout.to') }}</label>
                        <input type="date" name="to" class="{{ $input }} w-40">
                    </div>
                    <x-gp247::button type="submit" variant="secondary" size="sm" data-testid="multi-vendor-pro-payment-export-submit">
                        <i class="fas fa-file-excel"></i> {{ gp247_language_render('multi_vendor.payout.export') }}
                    </x-gp247::button>
                </form>
                @else
                <div class="mb-3">
                    @include('Plugins/MultiVendor::Admin.partials.pro_upsell', [
                        'audience'     => \App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::ROOT,
                        'variant'      => 'block',
                        'flag'         => \App\GP247\Plugins\MultiVendor\Tier\Tier::F_PAYOUT_STATEMENT,
                        'featureTitle' => gp247_language_render('multi_vendor.payout.export'),
                        'featureDesc'  => gp247_language_render(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_PAYOUT_STATEMENT)),
                        'gatewayUrl'   => \App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::gatewayUrl(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::ROOT, 'report'),
                    ])
                </div>
                @endif
                @if (!\App\GP247\Plugins\MultiVendor\Tier\Tier::allows(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_CLAWBACK))
                <div class="mb-3">
                    @include('Plugins/MultiVendor::Admin.partials.pro_upsell', [
                        'audience'     => \App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::ROOT,
                        'variant'      => 'block',
                        'flag'         => \App\GP247\Plugins\MultiVendor\Tier\Tier::F_CLAWBACK,
                        'featureTitle' => gp247_language_render(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::titleKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_CLAWBACK)),
                        'featureDesc'  => gp247_language_render(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_CLAWBACK)),
                        'gatewayUrl'   => \App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::gatewayUrl(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::ROOT, 'report'),
                    ])
                </div>
                @endif
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            @foreach ([
                                gp247_language_render('front.store_list'),
                                gp247_language_render('multi_vendor.payment.order_count'),
                                gp247_language_render('multi_vendor.payment.total_sum'),
                                gp247_language_render('multi_vendor.payment.amount'),
                                gp247_language_render('multi_vendor.payment.content'),
                                gp247_language_render('multi_vendor.payment.date_process'),
                                gp247_language_render('multi_vendor.payment.date_pay'),
                                gp247_language_render('multi_vendor.payout.column'),
                                gp247_language_render('multi_vendor.payment.status'),
                                gp247_language_render('action.title'),
                            ] as $th)
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $th }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @forelse ($payments as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30" wire:key="pay-{{ $row->id }}"
                            data-testid="multi-vendor-pro-payment-row">
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $row->store_id }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">x {{ number_format($row->order_count) }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{!! gp247_currency_render_symbol($row->total_sum, $row->currency) !!}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                {!! gp247_currency_render_symbol($row->amount, $row->currency) !!} (Rate: {{ $row->commission_rate }}%)
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $row->content }}
                            @if (\App\GP247\Plugins\MultiVendor\Payout\Payout::isAdjustment($row->kind ?? null))
                                <span class="ml-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900 dark:text-amber-200" data-testid="multi-vendor-payment-kind">{{ \App\GP247\Plugins\MultiVendor\Payout\Payout::kindLabel($row->kind) }}</span>
                            @endif</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $row->date_process }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $row->date_pay }}</td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300" data-testid="multi-vendor-pro-payment-payout-cell">
                                {{ $row->payout_account ?: ($row->payout_method ?: '—') }}
                                @if (!empty($row->payout_reference))<br><span class="text-gray-400">#{{ $row->payout_reference }}</span>@endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $mapcolor[$row->status] ?? '' }}">{{ $row->status }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="relative inline-block text-left" x-data="{ open: false }">
                                    <button type="button" x-on:click="open = !open"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div x-show="open" x-cloak x-on:click.outside="open = false"
                                        class="absolute end-0 z-20 mt-1 w-44 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                                        <a href="{{ gp247_route_admin('admin_MultiVendorPayment.edit', ['id' => $row->id]) }}"
                                            class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                                            data-testid="multi-vendor-pro-payment-edit-{{ $row->id }}">
                                            <i class="fa fa-edit"></i> {{ gp247_language_render('action.edit') }}
                                        </a>
                                        <button type="button"
                                            wire:click="delete('{{ $row->id }}')"
                                            wire:confirm="{{ gp247_language_render('action.delete_confirm') }}"
                                            class="flex w-full items-center gap-2 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                                            data-testid="multi-vendor-pro-payment-delete-{{ $row->id }}">
                                            <i class="fas fa-trash-alt"></i> {{ gp247_language_render('action.remove') }}
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center">
                                <i class="fas fa-box-open mb-3 block text-3xl text-gray-300 dark:text-gray-600"></i>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.no_data') }}</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-slot:footer>
                <div>{{ $payments->links() }}</div>
            </x-slot:footer>
        </x-gp247::card>
    </div>
@endif
</div>
