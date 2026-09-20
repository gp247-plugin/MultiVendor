@extends('gp247-shop-front::email.shop_layout')

@section('main')
<div style="max-width:600px;margin:0 auto;padding:20px;font-family:Arial,sans-serif;">
    <h2 style="color:#2c3e50;">{{ gp247_language_render('multi_vendor.mail.pending_review.title_'.$kind) }}</h2>
    <div style="background-color:#ffffff;padding:20px;border-radius:5px;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('multi_vendor.store') }}:</strong> {{ $storeName }}</p>
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('multi_vendor.mail.pending_review.label_'.$kind) }}:</strong> {{ $subject }}</p>
        @if ($code !== '')
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('multi_vendor.mail.pending_review.code') }}:</strong> {{ $code }}</p>
        @endif
    </div>
    <p style="margin-top:15px;">{{ gp247_language_render('multi_vendor.mail.pending_review.action_'.$kind) }}</p>
</div>
@endsection
