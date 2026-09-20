{{--
    Vendor order list (Livewire) — single full-width list + filters.
    Ports VendorOrderController::index into a Livewire screen; the order-detail
    link still points at the existing controller route vendor_admin_order.detail.

    @aidlc-unit multi-vendor-pro
    @aidlc-story US-multi-vendor-pro-vendor-admin-livewire
    @aidlc-adr multi-vendor_admin-livewire-migration
--}}
@php
    // No w-full: filters sit inline in the header (a w-full base made them stack);
    // each control carries its own width class below.
    $input = 'rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';

    // Tailwind tint per status style name; the style name comes from the model's
    // map, so these classes are on the plugin's Tailwind safelist.
    $statusTint = [
        'success'   => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200',
        'primary'   => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200',
        'info'      => 'bg-sky-100 text-sky-700 dark:bg-sky-900 dark:text-sky-200',
        'warning'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-200',
        'danger'    => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200',
        'secondary' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200',
    ];
    $fallbackTint = 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200';
@endphp

<x-gp247::card>
    <x-slot:header>
        <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">
            {{ gp247_language_render('admin.order.list') }}
        </h3>
        <div class="flex flex-wrap items-center gap-2">
            <select class="{{ $input }} w-auto" wire:model.live="order_status" data-testid="multi-vendor-pro-order-status">
                <option value="">{{ gp247_language_render('admin.order.search_order_status') }}</option>
                @foreach ($statusOrder as $key => $status)
                    <option value="{{ $key }}">{{ $status }}</option>
                @endforeach
            </select>

            <input type="date" class="{{ $input }} w-auto" wire:model.live="from_to"
                title="{{ gp247_language_render('action.from') }}" data-testid="multi-vendor-pro-order-from">
            <input type="date" class="{{ $input }} w-auto" wire:model.live="end_to"
                title="{{ gp247_language_render('action.to') }}" data-testid="multi-vendor-pro-order-to">

            <input type="search" class="{{ $input }} w-44" wire:model.live.debounce.400ms="keyword"
                placeholder="{{ gp247_language_render('admin.order.search_email') }}"
                data-testid="multi-vendor-pro-order-search">
        </div>
    </x-slot:header>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">
                        <button type="button" wire:click="setSort('email')" class="inline-flex items-center gap-1 uppercase hover:text-gray-700 dark:hover:text-gray-200" data-testid="multi-vendor-pro-order-sort-email">
                            {{ gp247_language_render('order.email') }}
                            @if ($sortField === 'email')<i class="fa fa-sort-{{ $sortDir === 'asc' ? 'up' : 'down' }}"></i>@else<i class="fa fa-sort text-gray-300"></i>@endif
                        </button>
                    </th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-gray-500"><i class="fa fa-shopping-cart" title="{{ gp247_language_render('order.subtotal') }}"></i></th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-gray-500"><i class="fa fa-truck" title="{{ gp247_language_render('order.shipping') }}"></i></th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-gray-500"><i class="fa fa-tags" title="{{ gp247_language_render('order.discount') }}"></i></th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-gray-500">{{ gp247_language_render('order.tax') }}</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-gray-500">{{ gp247_language_render('order.total') }}</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500"><i class="fa fa-credit-card" title="{{ gp247_language_render('admin.order.payment_method_short') }}"></i></th>
                    <th class="px-3 py-2 text-center text-xs font-semibold uppercase text-gray-500">{{ gp247_language_render('order.status') }}</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">
                        <button type="button" wire:click="setSort('created_at')" class="inline-flex items-center gap-1 uppercase hover:text-gray-700 dark:hover:text-gray-200" data-testid="multi-vendor-pro-order-sort-created_at">
                            {{ gp247_language_render('admin.created_at') }}
                            @if ($sortField === 'created_at')<i class="fa fa-sort-{{ $sortDir === 'asc' ? 'up' : 'down' }}"></i>@else<i class="fa fa-sort text-gray-300"></i>@endif
                        </button>
                    </th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-gray-500">{{ gp247_language_render('action.title') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                @foreach ($rows as $row)
                    @php
                        $tint = $statusTint[$styleMap[$row['status']] ?? 'secondary'] ?? $fallbackTint;
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30" wire:key="order-{{ $row['id'] }}" data-testid="multi-vendor-pro-order-row">
                        <td class="px-3 py-2 text-gray-800 dark:text-gray-100">{{ $row['email'] ?? 'N/A' }}</td>
                        <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-300">{{ gp247_currency_render_symbol($row['subtotal'] ?? 0, $row['currency']) }}</td>
                        <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-300">{{ gp247_currency_render_symbol($row['shipping'] ?? 0, $row['currency']) }}</td>
                        <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-300">{{ gp247_currency_render_symbol($row['discount'] ?? 0, $row['currency']) }}</td>
                        <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-300">{{ gp247_currency_render_symbol($row['tax'] ?? 0, $row['currency']) }}</td>
                        <td class="px-3 py-2 text-right font-medium text-gray-800 dark:text-gray-100">{{ gp247_currency_render_symbol($row['total'] ?? 0, $row['currency']) }}</td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ ($row['payment_method'] ?? 'N/A').'('.$row['currency'].'/'.$row['exchange_rate'].')' }}</td>
                        <td class="px-3 py-2 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $tint }}">{{ $statusOrder[$row['status']] ?? $row['status'] }}</span>
                        </td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $row['created_at'] }}</td>
                        <td class="px-3 py-2">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ gp247_route_admin('vendor_admin_order.detail', ['id' => $row['id'] ? $row['id'] : 'not-found-id']) }}"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-blue-600 transition hover:bg-blue-50 dark:hover:bg-blue-900/40"
                                    title="{{ gp247_language_render('admin.order.edit') }}"
                                    data-testid="multi-vendor-pro-order-edit">
                                    <i class="fa fa-edit"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $rows->links() }}</div>
</x-gp247::card>
