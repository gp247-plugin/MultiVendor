@extends('gp247-shop-front::email.shop_layout')

@section('main')
<div style="max-width:600px;margin:0 auto;padding:20px;font-family:Arial,sans-serif;">
    <h2 style="color:#2c3e50;">{{ gp247_language_render('multi_vendor.mail.vendor_rejected.title', ['store' => $storeName]) }}</h2>
    <p>{{ gp247_language_render('multi_vendor.mail.vendor_rejected.intro') }}</p>
    <div style="background-color:#fff7ed;border-left:4px solid #f59e0b;padding:12px 15px;margin:15px 0;">
        <strong>{{ gp247_language_render('multi_vendor.review.reason') }}:</strong> {{ $reason }}
    </div>
    <p>{{ gp247_language_render('multi_vendor.mail.vendor_rejected.next') }}</p>
    @if ($loginUrl !== '')
    <p style="text-align:center;margin-top:20px;"><a href="{{ $loginUrl }}" style="display:inline-block;padding:10px 18px;background:#2c3e50;color:#fff;text-decoration:none;border-radius:4px;">{{ gp247_language_render('multi_vendor.mail.vendor_approved.button') }}</a></p>
    @endif
</div>
@endsection
