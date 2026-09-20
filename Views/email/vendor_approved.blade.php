@extends('gp247-shop-front::email.shop_layout')

@section('main')
<div style="max-width:600px;margin:0 auto;padding:20px;font-family:Arial,sans-serif;">
    <h2 style="color:#2c3e50;">{{ gp247_language_render('multi_vendor.mail.vendor_approved.title', ['store' => $storeName]) }}</h2>
    <p>{{ gp247_language_render('multi_vendor.mail.vendor_approved.intro') }}</p>
    @if ($storeUrl !== '')
    <p><strong>{{ gp247_language_render('multi_vendor.mail.vendor_approved.store_url') }}:</strong> <a href="{{ $storeUrl }}">{{ $storeUrl }}</a></p>
    @endif
    @if ($loginUrl !== '')
    <p style="text-align:center;margin-top:20px;"><a href="{{ $loginUrl }}" style="display:inline-block;padding:10px 18px;background:#2c3e50;color:#fff;text-decoration:none;border-radius:4px;">{{ gp247_language_render('multi_vendor.mail.vendor_approved.button') }}</a></p>
    @endif
</div>
@endsection
