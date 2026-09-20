@extends($templatePathAdminVendor.'layout')

@section('main')

@php
    $details = $order->details;
    $tdTitle = 'w-2/5 px-4 py-2 font-semibold text-gray-700 dark:text-gray-200';
    $tdLabel = 'w-2/5 px-4 py-2 text-gray-600 dark:text-gray-300';
    $tdValue = 'px-4 py-2 text-gray-700 dark:text-gray-200';
    $tableClass = 'w-full text-sm';
    $panelClass = 'overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700';
    $rowClass = 'border-b border-gray-100 last:border-0 dark:border-gray-700/50';
@endphp

<x-gp247::card>
    <x-slot:header>
        <div>
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                {{ gp247_language_render('order.detail') }} #{{ $order->id }}
            </h3>
        </div>
        <x-gp247::button variant="secondary" size="sm"
            href="{{ gp247_route_admin('vendor_admin_order.index') }}"
            title="{{ gp247_language_render('admin.back_list') }}">
            <i class="fas fa-list"></i>
            {{ gp247_language_render('admin.back_list') }}
        </x-gp247::button>
    </x-slot:header>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2" id="order-body">

        {{-- Customer --}}
        <div class="{{ $panelClass }}">
            <table class="{{ $tableClass }}">
                <tbody>
                    <tr class="{{ $rowClass }}"><td class="{{ $tdTitle }}">{{ gp247_language_render('order.first_name') }}:</td><td class="{{ $tdValue }}">{!! $order->first_name !!}</td></tr>

                    @if (gp247_config_admin('customer_lastname'))
                    <tr class="{{ $rowClass }}"><td class="{{ $tdTitle }}">{{ gp247_language_render('order.last_name') }}:</td><td class="{{ $tdValue }}">{!! $order->last_name !!}</td></tr>
                    @endif

                    @if (gp247_config_admin('customer_phone'))
                    <tr class="{{ $rowClass }}"><td class="{{ $tdTitle }}">{{ gp247_language_render('order.phone') }}:</td><td class="{{ $tdValue }}">{!! $order->phone !!}</td></tr>
                    @endif

                    <tr class="{{ $rowClass }}"><td class="{{ $tdTitle }}">{{ gp247_language_render('order.email') }}:</td><td class="{{ $tdValue }}">{!! empty($order->email) ? 'N/A' : $order->email !!}</td></tr>

                    @if (gp247_config_admin('customer_company'))
                    <tr class="{{ $rowClass }}"><td class="{{ $tdTitle }}">{{ gp247_language_render('order.company') }}:</td><td class="{{ $tdValue }}">{!! $order->company !!}</td></tr>
                    @endif

                    @if (gp247_config_admin('customer_postcode'))
                    <tr class="{{ $rowClass }}"><td class="{{ $tdTitle }}">{{ gp247_language_render('order.postcode') }}:</td><td class="{{ $tdValue }}">{!! $order->postcode !!}</td></tr>
                    @endif

                    <tr class="{{ $rowClass }}"><td class="{{ $tdTitle }}">{{ gp247_language_render('order.address1') }}:</td><td class="{{ $tdValue }}">{!! $order->address1 !!}</td></tr>

                    @if (gp247_config_admin('customer_address2'))
                    <tr class="{{ $rowClass }}"><td class="{{ $tdTitle }}">{{ gp247_language_render('order.address2') }}:</td><td class="{{ $tdValue }}">{!! $order->address2 !!}</td></tr>
                    @endif

                    @if (gp247_config_admin('customer_country'))
                    <tr class="{{ $rowClass }}"><td class="{{ $tdTitle }}">{{ gp247_language_render('order.country') }}:</td><td class="{{ $tdValue }}">{{ $country[$order->country] ?? $order->country }}</td></tr>
                    @endif
                </tbody>
            </table>
        </div>

        {{-- Status + meta --}}
        <div class="space-y-5">
            <div class="{{ $panelClass }}">
                <table class="{{ $tableClass }}">
                    <tbody>
                        <tr class="{{ $rowClass }}"><td class="{{ $tdTitle }}">{{ gp247_language_render('order.status') }}:</td><td class="{{ $tdValue }}">{{ $statusOrder[$order->status] ?? '' }}</td></tr>

                        <tr class="{{ $rowClass }}">
                            <td class="{{ $tdLabel }}">{{ gp247_language_render('order.shipping_status') }}:</td>
                            <td class="{{ $tdValue }}">
                                {{-- Editable inline, as in v1 (x-editable there). --}}
                                @include($templatePathAdminVendor.'component.inline_field', [
                                    'url' => gp247_route_admin('vendor_admin_order.update'),
                                    'storeId' => session('adminStoreId'),
                                    'name' => 'shipping_status',
                                    'type' => 'select',
                                    'value' => $order->shipping_status,
                                    'options' => $statusShipping,
                                    'extra' => ['pk' => $order->id],
                                ])
                            </td>
                        </tr>

                        <tr class="{{ $rowClass }}"><td class="{{ $tdLabel }}">{{ gp247_language_render('order.payment_status') }}:</td><td class="{{ $tdValue }}">{{ $statusPayment[$order->payment_status] ?? '' }}</td></tr>
                        <tr class="{{ $rowClass }}"><td class="{{ $tdLabel }}">{{ gp247_language_render('order.shipping_method') }}:</td><td class="{{ $tdValue }}">{{ $order->shipping_method }}</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="{{ $panelClass }}">
                <table class="{{ $tableClass }}">
                    <tbody>
                        <tr class="{{ $rowClass }}"><td class="{{ $tdLabel }}"><i class="far fa-money-bill-alt text-gray-400"></i> {{ gp247_language_render('order.currency') }}:</td><td class="{{ $tdValue }}">{{ $order->currency }}</td></tr>
                        <tr class="{{ $rowClass }}"><td class="{{ $tdLabel }}">{{ gp247_language_render('order.domain') }}:</td><td class="{{ $tdValue }}">{{ $order->domain }}</td></tr>
                        <tr class="{{ $rowClass }}"><td class="{{ $tdLabel }}">{{ gp247_language_render('admin.created_at') }}:</td><td class="{{ $tdValue }}">{{ $order->created_at }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Lines --}}
    <div class="mt-5 overflow-x-auto {{ $panelClass }}">
        <table class="{{ $tableClass }}">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('product.name') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('product.sku') }}</th>
                    <th class="w-32 px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('product.price') }}</th>
                    <th class="w-20 px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('product.quantity') }}</th>
                    <th class="w-32 px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('product.total_price') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('product.tax') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                @foreach ($details as $item)
                <tr>
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                        {{ $item->name }}
                        @php
                            $html = '';
                            if ($item->attribute && is_array(json_decode($item->attribute, true))) {
                                $array = json_decode($item->attribute, true);
                                foreach ($array as $key => $element) {
                                    $html .= '<br><b>'.$attributesGroup[$key].'</b> : <i>'.gp247_render_option_price($element, $order->currency, $order->exchange_rate).'</i>';
                                }
                            }
                        @endphp
                        {!! $html !!}
                    </td>
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $item->sku }}</td>
                    <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200">{{ $item->price }}</td>
                    <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200">x {{ $item->qty }}</td>
                    <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200">{{ gp247_currency_render_symbol($item->total_price, $order->currency) }}</td>
                    <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200">{{ $item->tax }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
        {{-- Totals --}}
        <div class="{{ $panelClass }}">
            <table class="{{ $tableClass }}">
                <tbody>
                    @foreach ($dataTotal as $element)
                        @switch($element['code'])
                            @case('subtotal')
                            @case('tax')
                                <tr class="{{ $rowClass }}">
                                    <td class="{{ $tdLabel }}">{!! $element['title'] !!}:</td>
                                    <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-200 data-{{ $element['code'] }}">{{ gp247_currency_format($element['value']) }}</td>
                                </tr>
                                @break

                            @case('shipping')
                                <tr class="{{ $rowClass }}">
                                    <td class="{{ $tdLabel }}">{!! $element['title'] !!}:</td>
                                    <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-200">{{ $element['value'] }}</td>
                                </tr>
                                @break

                            @case('discount')
                            @case('received')
                                <tr class="{{ $rowClass }}">
                                    <td class="{{ $tdLabel }}">{!! $element['title'] !!}(-):</td>
                                    <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-200">{{ $element['value'] }}</td>
                                </tr>
                                @break

                            @case('total')
                                <tr class="{{ $rowClass }} bg-gray-50 font-bold dark:bg-gray-700/50">
                                    <td class="{{ $tdLabel }} font-bold">{!! $element['title'] !!}:</td>
                                    <td class="px-4 py-2 text-right text-gray-800 dark:text-gray-100 data-{{ $element['code'] }}">{{ gp247_currency_format($element['value']) }}</td>
                                </tr>
                                @break
                        @endswitch
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Customer note --}}
        <div class="{{ $panelClass }}">
            <table class="{{ $tableClass }}">
                <tbody>
                    <tr>
                        <td class="{{ $tdTitle }}">{{ gp247_language_render('order.note') }}:</td>
                        <td class="{{ $tdValue }}">{{ $order->comment }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</x-gp247::card>

@endsection
