{{--
    Vendor order detail (Livewire) — view one order + inline shipping-status update.
    Ports VendorOrderController::edit (display) and postOrderUpdate (the inline
    shipping_status write) into a single Livewire screen. Every value is scoped to
    the signed-in vendor's own store by the component.

    @aidlc-unit multi-vendor-pro
    @aidlc-story US-multi-vendor-pro-vendor-admin-livewire
    @aidlc-adr multi-vendor_admin-livewire-migration
--}}
@php
    $details = $order->details;
    $tdTitle = 'w-2/5 px-4 py-2 font-semibold text-gray-700 dark:text-gray-200';
    $tdLabel = 'w-2/5 px-4 py-2 text-gray-600 dark:text-gray-300';
    $tdValue = 'px-4 py-2 text-gray-700 dark:text-gray-200';
    $tableClass = 'w-full text-sm';
    $panelClass = 'overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700';
    $rowClass = 'border-b border-gray-100 last:border-0 dark:border-gray-700/50';
    $input = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
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
        <x-gp247::button variant="secondary" size="sm" href="{{ $printUrl }}" target="_blank"
            data-testid="multi-vendor-pro-order-detail-print">
            <i class="fas fa-print"></i>
            {{ gp247_language_render('multi_vendor.order.print_slip') }}
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
                        <tr class="{{ $rowClass }}">
                            <td class="{{ $tdTitle }}">{{ gp247_language_render('order.status') }}:</td>
                            <td class="{{ $tdValue }}">
                                @if (!empty($allowedStatuses))
                                    {{-- S1-3: only policy-allowed targets are offered; the write is ShopOrder::changeStatus(). --}}
                                    <form wire:submit.prevent="changeOrderStatus" class="flex items-center gap-2">
                                        <select class="{{ $input }} w-auto" wire:model="order_status"
                                            data-testid="multi-vendor-pro-order-detail-order-status">
                                            <option value="{{ $order->status }}">{{ $statusOrder[$order->status] ?? $order->status }}</option>
                                            @foreach ($allowedStatuses as $sid)
                                                <option value="{{ $sid }}">→ {{ $statusOrder[$sid] ?? $sid }}</option>
                                            @endforeach
                                        </select>
                                        <x-gp247::button type="submit" variant="primary" size="sm"
                                            data-testid="multi-vendor-pro-order-detail-order-status-submit">
                                            <i class="fas fa-save"></i>
                                            {{ gp247_language_render('action.submit') }}
                                        </x-gp247::button>
                                    </form>
                                @else
                                    {{ $statusOrder[$order->status] ?? '' }}
                                    @if (!\App\GP247\Plugins\MultiVendor\Tier\Tier::allows(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_ORDER_FULFILLMENT))
                                        <div class="mt-2">
                                            @include('Plugins/MultiVendor::Admin.partials.pro_upsell', [
                                                'audience'     => \App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::VENDOR,
                                                'variant'      => 'block',
                                                'flag'         => \App\GP247\Plugins\MultiVendor\Tier\Tier::F_ORDER_FULFILLMENT,
                                                'featureTitle' => gp247_language_render(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::titleKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_ORDER_FULFILLMENT)),
                                                'featureDesc'  => gp247_language_render(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_ORDER_FULFILLMENT)),
                                                'gatewayUrl'   => \App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::gatewayUrl(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::VENDOR, 'kyc'),
                                            ])
                                        </div>
                                    @endif
                                @endif
                            </td>
                        </tr>

                        <tr class="{{ $rowClass }}">
                            <td class="{{ $tdLabel }}">{{ gp247_language_render('order.shipping_status') }}:</td>
                            <td class="{{ $tdValue }}">
                                {{-- Editable inline (postOrderUpdate → shipping_status only). --}}
                                <form wire:submit.prevent="updateStatus" class="flex items-center gap-2">
                                    <select class="{{ $input }} w-auto" wire:model="shipping_status"
                                        data-testid="multi-vendor-pro-order-detail-shipping-status">
                                        @foreach ($statusShipping as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <x-gp247::button type="submit" variant="primary" size="sm"
                                        data-testid="multi-vendor-pro-order-detail-submit">
                                        <i class="fas fa-save"></i>
                                        {{ gp247_language_render('action.submit') }}
                                    </x-gp247::button>
                                </form>
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

    {{-- S1-3: shipment details (plugin table vendor_order_shipment) --}}
    <div class="mt-5 {{ $panelClass }} p-4" data-testid="multi-vendor-pro-order-detail-shipment">
        <h3 class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">
            <i class="fas fa-truck mr-1 text-gray-400"></i>{{ gp247_language_render('multi_vendor.shipment.title') }}
        </h3>
        @if ($canEditShipping)
        <form wire:submit.prevent="saveShipment" class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ gp247_language_render('multi_vendor.shipment.carrier') }}</label>
                <input type="text" wire:model="carrier" class="{{ $input }}" maxlength="100" data-testid="multi-vendor-pro-order-detail-carrier">
                @error('carrier')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ gp247_language_render('multi_vendor.shipment.tracking_code') }}</label>
                <input type="text" wire:model="tracking_code" class="{{ $input }}" maxlength="100" data-testid="multi-vendor-pro-order-detail-tracking">
                @error('tracking_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ gp247_language_render('multi_vendor.shipment.note') }}</label>
                <input type="text" wire:model="shipment_note" class="{{ $input }}" maxlength="255" data-testid="multi-vendor-pro-order-detail-shipment-note">
                @error('shipment_note')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-end">
                <x-gp247::button type="submit" variant="primary" size="sm" data-testid="multi-vendor-pro-order-detail-shipment-submit">
                    <i class="fas fa-save"></i> {{ gp247_language_render('action.submit') }}
                </x-gp247::button>
            </div>
        </form>
        @else
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ $carrier !== '' || $tracking_code !== '' ? $carrier.' '.$tracking_code : gp247_language_render('multi_vendor.order.status_locked') }}
        </p>
        @endif
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

        {{-- S3-3: customer dispute (Pro) — the vendor answers first --}}
        @if (!empty($dispute))
        <div class="mt-5 {{ $panelClass }} p-4" data-testid="multi-vendor-dispute-vendor-panel">
            <h3 class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">
                <i class="fas fa-gavel mr-1 text-gray-400"></i>{{ gp247_language_render('multi_vendor.dispute.title') }}
                <span class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $dispute->status === 'escalated' ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200' : ($dispute->status === 'resolved' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200' : 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-200') }}" data-testid="multi-vendor-dispute-vendor-status">{{ gp247_language_render('multi_vendor.dispute.status_'.$dispute->status) }}</span>
            </h3>
            <dl class="grid grid-cols-1 gap-2 text-sm md:grid-cols-2">
                <div><dt class="text-xs text-gray-500">{{ gp247_language_render('multi_vendor.dispute.type') }}</dt><dd class="text-gray-800 dark:text-gray-100">{{ gp247_language_render('multi_vendor.dispute.type_'.$dispute->type) }}</dd></div>
                <div><dt class="text-xs text-gray-500">{{ gp247_language_render('multi_vendor.dispute.requested_amount') }}</dt><dd class="text-gray-800 dark:text-gray-100">{{ $dispute->requested_amount ? gp247_currency_format($dispute->requested_amount, $dispute->currency) : '—' }} <span class="text-xs text-gray-400">({{ gp247_language_render('multi_vendor.dispute.refundable') }}: {{ gp247_currency_format($disputeRefundable, $dispute->currency) }})</span></dd></div>
                <div class="md:col-span-2"><dt class="text-xs text-gray-500">{{ gp247_language_render('multi_vendor.dispute.reason') }}</dt><dd class="text-gray-800 dark:text-gray-100">{{ $dispute->reason }}</dd></div>
                @if ($dispute->vendor_response)<div class="md:col-span-2"><dt class="text-xs text-gray-500">{{ gp247_language_render('multi_vendor.dispute.vendor_response') }}</dt><dd class="text-gray-800 dark:text-gray-100">{{ $dispute->vendor_response }}</dd></div>@endif
                @if ($dispute->resolution)<div class="md:col-span-2"><dt class="text-xs text-gray-500">{{ gp247_language_render('multi_vendor.dispute.resolution') }}</dt><dd class="text-gray-800 dark:text-gray-100"><strong>{{ gp247_language_render('multi_vendor.dispute.resolution_'.$dispute->resolution) }}</strong>@if ($dispute->resolution_amount) — {{ gp247_currency_format($dispute->resolution_amount, $dispute->currency) }}@endif @if ($dispute->resolution_note)<div class="text-xs text-gray-500">{{ $dispute->resolution_note }}</div>@endif</dd></div>@endif
            </dl>
            @if ($dispute->status === 'open')
                <p class="mt-3 text-xs text-amber-700 dark:text-amber-300">{{ gp247_language_render('multi_vendor.dispute.vendor_deadline', ['date' => optional($dispute->deadline_at)->format('Y-m-d H:i')]) }}</p>
                <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ gp247_language_render('multi_vendor.dispute.refund_amount') }}</label>
                        <input type="number" step="any" min="0" max="{{ $disputeRefundable }}" wire:model="disputeAmount" class="{{ $input }}" data-testid="multi-vendor-dispute-vendor-amount">
                        @error('disputeAmount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ gp247_language_render('multi_vendor.dispute.vendor_note') }}</label>
                        <input type="text" wire:model="disputeNote" class="{{ $input }}" maxlength="1000" data-testid="multi-vendor-dispute-vendor-note">
                        @error('disputeNote')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-3 flex justify-end gap-2">
                        <x-gp247::button variant="secondary" size="sm" wire:click="rejectDispute" data-testid="multi-vendor-dispute-vendor-reject"><i class="fas fa-times"></i> {{ gp247_language_render('multi_vendor.dispute.vendor_reject') }}</x-gp247::button>
                        <x-gp247::button variant="primary" size="sm" wire:click="acceptDispute" data-testid="multi-vendor-dispute-vendor-accept"><i class="fas fa-check"></i> {{ gp247_language_render('multi_vendor.dispute.vendor_accept') }}</x-gp247::button>
                    </div>
                </div>
            @elseif ($dispute->status === 'escalated')
                <p class="mt-3 text-xs text-gray-500">{{ gp247_language_render('multi_vendor.dispute.escalated_hint') }}</p>
            @endif
        </div>
        @endif

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
