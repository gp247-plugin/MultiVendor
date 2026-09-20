@extends('gp247-shop-front::email.shop_layout')

@section('main')
<div style="max-width:600px;margin:0 auto;padding:20px;font-family:Arial,sans-serif;">
    <h2 style="color:#2c3e50;">{{ gp247_language_render('multi_vendor.mail.dispute_vendor_responded.title', ['order' => $orderId]) }}</h2>
    <p>{{ gp247_language_render('multi_vendor.mail.dispute_vendor_responded.intro', ['store' => $storeName]) }}</p>
    <div style="background-color:#fff3cd;padding:15px;border-radius:5px;margin:15px 0;">
        <strong>{{ gp247_language_render('multi_vendor.dispute.vendor_response') }}:</strong> {{ $response }}
    </div>
    <p style="color:#7f8c8d;">{{ gp247_language_render('multi_vendor.mail.dispute_vendor_responded.next') }}</p>
    @if ($url !== '')
    <p style="text-align:center;margin-top:20px;"><a href="{{ $url }}" style="display:inline-block;padding:10px 18px;background:#2c3e50;color:#fff;text-decoration:none;border-radius:4px;">{{ gp247_language_render('multi_vendor.mail.dispute_opened.button') }}</a></p>
    @endif
</div>
@endsection
