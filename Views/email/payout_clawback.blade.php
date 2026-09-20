@extends('gp247-shop-front::email.shop_layout')

@section('main')
<div style="max-width:600px;margin:0 auto;padding:20px;font-family:Arial,sans-serif;">
    <h2 style="color:#2c3e50;">{{ gp247_language_render('multi_vendor.mail.payout_clawback.title', ['store' => $storeName]) }}</h2>
    <p>{{ gp247_language_render('multi_vendor.mail.payout_clawback.intro') }}</p>
    <div style="background-color:#ffffff;padding:20px;border-radius:5px;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('multi_vendor.clawback.kind') }}:</strong> {{ $kindLabel }}</p>
        @if ($orderId !== '')
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('order.id') }}:</strong> #{{ $orderId }}</p>
        @endif
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('multi_vendor.payment.total_sum') }}:</strong> {{ $totalSum }}</p>
        <p style="margin:5px 0;font-size:1.2em;"><strong>{{ gp247_language_render('multi_vendor.payment.amount') }}:</strong> {{ $amount }}</p>
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('multi_vendor.payment.date_process') }}:</strong> {{ $dateProcess }}</p>
        <p style="margin:5px 0;color:#7f8c8d;">{{ gp247_language_render('multi_vendor.mail.payout_clawback.netting') }}</p>
        @if ($content !== '')
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('multi_vendor.payment.content') }}:</strong> {{ $content }}</p>
        @endif
    </div>
    @if ($paymentUrl !== '')
    <p style="text-align:center;margin-top:20px;"><a href="{{ $paymentUrl }}" style="display:inline-block;padding:10px 18px;background:#2c3e50;color:#fff;text-decoration:none;border-radius:4px;">{{ gp247_language_render('multi_vendor.mail.payout_done.button') }}</a></p>
    @endif
</div>
@endsection
