@extends('gp247-shop-front::email.shop_layout')

@section('main')
<div style="max-width:600px;margin:0 auto;padding:20px;font-family:Arial,sans-serif;">
    <h2 style="color:#2c3e50;">{{ gp247_language_render('multi_vendor.mail.payout_done.title', ['store' => $storeName]) }}</h2>
    <p>{{ gp247_language_render('multi_vendor.mail.payout_done.intro') }}</p>
    <div style="background-color:#ffffff;padding:20px;border-radius:5px;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('multi_vendor.payment.date_process') }}:</strong> {{ $dateProcess }}</p>
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('multi_vendor.payment.date_pay') }}:</strong> {{ $datePay }}</p>
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('multi_vendor.payment.order_count') }}:</strong> {{ $orderCount }}</p>
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('multi_vendor.payment.total_sum') }}:</strong> {{ $totalSum }}</p>
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('multi_vendor.mail.payout_done.vendor_share') }}:</strong> {{ $commissionRate }}%</p>
        <p style="margin:5px 0;font-size:1.2em;"><strong>{{ gp247_language_render('multi_vendor.payment.amount') }}:</strong> {{ $amount }}</p>
        @if ($payoutAccount !== '' || $payoutMethod !== '')
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('multi_vendor.payout.title') }}:</strong> {{ $payoutAccount !== '' ? $payoutAccount : $payoutMethod }}</p>
        @endif
        @if ($payoutReference !== '')
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('multi_vendor.payout.reference') }}:</strong> {{ $payoutReference }}</p>
        @endif
        @if ($comment !== '')
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('multi_vendor.payment.content') }}:</strong> {{ $comment }}</p>
        @endif
    </div>
    @if ($paymentUrl !== '')
    <p style="text-align:center;margin-top:20px;"><a href="{{ $paymentUrl }}" style="display:inline-block;padding:10px 18px;background:#2c3e50;color:#fff;text-decoration:none;border-radius:4px;">{{ gp247_language_render('multi_vendor.mail.payout_done.button') }}</a></p>
    @endif
</div>
@endsection
