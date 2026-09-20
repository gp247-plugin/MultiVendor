{{--
    Dispute box on the customer's order page (S3-3) — rendered through the
    storefront extension point `shop_order_detail_bottom`. Shows the current
    dispute (status, vendor answer, marketplace decision) and, when the order
    is eligible, the form to open one. GP247Front bundle classes + inline
    style only (no plugin admin.css on the storefront).

    @aidlc-unit multi-vendor-pro
    @aidlc-story US-multi-vendor-pro-order-dispute

    Variables: $order, $dispute (?VendorDispute), $ineligible (?string key),
               $refundable, $types, $notice, $noticeType.
--}}
@php
    $t = fn (string $k, array $p = []) => gp247_language_render('multi_vendor.dispute.'.$k, $p);
    $badge = ['open' => '#b45309', 'escalated' => '#b91c1c', 'resolved' => '#047857', 'withdrawn' => '#6b7280'];
    $canWithdraw = $dispute && $dispute->status === 'open';
@endphp
<div class="mt-6 rounded-xl border border-ink-100 bg-white p-4" data-testid="multi-vendor-dispute-box">
    <h3 class="text-base font-semibold text-ink-900">{{ $t('title') }}</h3>

    @if (!empty($notice))
        <p class="mt-2 text-sm" style="color: {{ $noticeType === 'error' ? '#b91c1c' : '#047857' }};" data-testid="multi-vendor-dispute-notice">{{ $notice }}</p>
    @endif

    @if ($dispute)
        <div class="mt-3 text-sm text-ink-700 space-y-1" data-testid="multi-vendor-dispute-status">
            <div>
                <span class="text-ink-500">{{ $t('status') }}:</span>
                <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold text-white" style="background: {{ $badge[$dispute->status] ?? '#6b7280' }};">{{ $t('status_'.$dispute->status) }}</span>
                <span class="text-ink-400 text-xs">#{{ $dispute->id }} · {{ $dispute->created_at }}</span>
            </div>
            <div><span class="text-ink-500">{{ $t('type') }}:</span> {{ $t('type_'.$dispute->type) }}</div>
            <div><span class="text-ink-500">{{ $t('reason') }}:</span> {{ $dispute->reason }}</div>
            @if ($dispute->requested_amount)
                <div><span class="text-ink-500">{{ $t('requested_amount') }}:</span> {{ gp247_currency_format($dispute->requested_amount) }}</div>
            @endif
            @if ($dispute->vendor_response)
                <div><span class="text-ink-500">{{ $t('vendor_response') }}:</span> {{ $dispute->vendor_response }}</div>
            @endif
            @if ($dispute->status === 'open' && $dispute->deadline_at)
                <div class="text-xs text-ink-400">{{ $t('vendor_deadline', ['date' => $dispute->deadline_at->format('Y-m-d H:i')]) }}</div>
            @endif
            @if ($dispute->status === 'escalated')
                <div class="text-xs text-ink-400">{{ $t('escalated_hint') }}</div>
            @endif
            @if ($dispute->status === 'resolved')
                <div>
                    <span class="text-ink-500">{{ $t('resolution') }}:</span>
                    <strong>{{ $t('resolution_'.$dispute->resolution) }}</strong>
                    @if ($dispute->resolution_amount) — {{ gp247_currency_format($dispute->resolution_amount) }} @endif
                    @if ($dispute->resolution_note)<div class="text-ink-600">{{ $dispute->resolution_note }}</div>@endif
                </div>
                @if ($dispute->resolution !== 'reject')
                    <div class="text-xs text-ink-400">{{ $t('refund_outside_hint') }}</div>
                @endif
            @endif
        </div>
        @if ($canWithdraw)
            <form method="post" action="{{ gp247_route_front('MultiVendor.dispute_withdraw', ['order' => $order->id]) }}" class="mt-3">
                @csrf
                <button type="submit" class="btn-outline btn-sm" data-testid="multi-vendor-dispute-withdraw">{{ $t('withdraw') }}</button>
            </form>
        @endif
    @endif

    @if ($ineligible === null)
        <form method="post" action="{{ gp247_route_front('MultiVendor.dispute_open', ['order' => $order->id]) }}" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2" data-testid="multi-vendor-dispute-form">
            @csrf
            <p class="md:col-span-2 text-sm text-ink-500">{{ $t('open_help', ['amount' => gp247_currency_format($refundable)]) }}</p>
            <div>
                <label class="block text-xs font-medium text-ink-500 mb-1">{{ $t('type') }} *</label>
                <select name="type" class="w-full rounded-lg border border-ink-200 px-3 py-2 text-sm" required data-testid="multi-vendor-dispute-type">
                    @foreach ($types as $type)
                        <option value="{{ $type }}">{{ $t('type_'.$type) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-ink-500 mb-1">{{ $t('requested_amount') }}</label>
                <input type="number" name="requested_amount" min="0" max="{{ $refundable }}" step="any" class="w-full rounded-lg border border-ink-200 px-3 py-2 text-sm" placeholder="{{ gp247_currency_format($refundable) }}" data-testid="multi-vendor-dispute-amount">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-ink-500 mb-1">{{ $t('reason') }} *</label>
                <textarea name="reason" rows="3" minlength="10" maxlength="2000" required class="w-full rounded-lg border border-ink-200 px-3 py-2 text-sm" data-testid="multi-vendor-dispute-reason"></textarea>
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button type="submit" class="btn-primary btn-sm" data-testid="multi-vendor-dispute-open">{{ $t('open') }}</button>
            </div>
        </form>
    @elseif (!$dispute || !in_array($dispute->status, ['open', 'escalated'], true))
        <p class="mt-3 text-sm text-ink-500" data-testid="multi-vendor-dispute-ineligible">{{ $t('ineligible_'.$ineligible) }}</p>
    @endif
</div>
