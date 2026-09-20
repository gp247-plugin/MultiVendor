@extends('gp247-shop-front::email.shop_layout')

@section('main')
<div style="max-width:600px;margin:0 auto;padding:20px;font-family:Arial,sans-serif;">
    <h2 style="color:#2c3e50;">{{ gp247_language_render('multi_vendor.mail.dispute_opened.title', ['order' => $orderId]) }}</h2>
    <p>{{ gp247_language_render('multi_vendor.mail.dispute_opened.intro', ['store' => $storeName]) }}</p>
    <div style="background-color:#ffffff;padding:20px;border-radius:5px;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('order.id') }}:</strong> #{{ $orderId }}</p>
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('multi_vendor.dispute.type') }}:</strong> {{ $typeLabel }}</p>
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('multi_vendor.dispute.reason') }}:</strong> {{ $reason }}</p>
        @if ($requested !== '')
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('multi_vendor.dispute.requested_amount') }}:</strong> {{ $requested }}</p>
        @endif
        <p style="margin:5px 0;color:#7f8c8d;">{{ gp247_language_render('multi_vendor.mail.dispute_opened.deadline', ['date' => $deadline]) }}</p>
    </div>
    @if ($url !== '')
    <p style="text-align:center;margin-top:20px;"><a href="{{ $url }}" style="display:inline-block;padding:10px 18px;background:#2c3e50;color:#fff;text-decoration:none;border-radius:4px;">{{ gp247_language_render('multi_vendor.mail.dispute_opened.button') }}</a></p>
    @endif
</div>
@endsection
