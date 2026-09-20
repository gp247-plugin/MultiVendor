@extends('gp247-shop-front::email.shop_layout')

@section('main')
<div style="max-width:600px;margin:0 auto;padding:20px;font-family:Arial,sans-serif;">
    <h2 style="color:#2c3e50;">{{ gp247_language_render($approved ? 'multi_vendor.mail.kyc_reviewed.title_approved' : 'multi_vendor.mail.kyc_reviewed.title_rejected', ['store' => $storeName]) }}</h2>
    <p>{{ gp247_language_render($approved ? 'multi_vendor.mail.kyc_reviewed.intro_approved' : 'multi_vendor.mail.kyc_reviewed.intro_rejected') }}</p>
    @if (!$approved && $reason !== '')
    <div style="background-color:#fff3cd;padding:15px;border-radius:5px;margin:15px 0;">
        <strong>{{ gp247_language_render('multi_vendor.kyc.rejected_reason') }}:</strong> {{ $reason }}
    </div>
    @endif
    @if ($kycUrl !== '')
    <p style="text-align:center;margin-top:20px;"><a href="{{ $kycUrl }}" style="display:inline-block;padding:10px 18px;background:#2c3e50;color:#fff;text-decoration:none;border-radius:4px;">{{ gp247_language_render('multi_vendor.mail.kyc_reviewed.button') }}</a></p>
    @endif
</div>
@endsection
