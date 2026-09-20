@extends('gp247-shop-front::email.shop_layout')

@section('main')
<div style="max-width:600px;margin:0 auto;padding:20px;font-family:Arial,sans-serif;">
    <h2 style="color:#2c3e50;">{{ gp247_language_render('multi_vendor.mail.order_created.title', ['store' => $storeName]) }}</h2>
    <p>{{ gp247_language_render('multi_vendor.mail.order_created.intro', ['order_id' => $orderId]) }}</p>
    <div style="background-color:#f8f9fa;padding:15px;border-radius:5px;margin:15px 0;">
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('order.id') }}:</strong> {{ $orderId }}</p>
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('order.full_name') }}:</strong> {{ $customerName }}</p>
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('order.email') }}:</strong> {{ $customerEmail }}</p>
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('order.phone') }}:</strong> {{ $customerPhone }}</p>
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('order.address') }}:</strong> {{ $address }}</p>
        @if ($comment !== '')
        <p style="margin:5px 0;"><strong>{{ gp247_language_render('order.order_note') }}:</strong> {{ $comment }}</p>
        @endif
    </div>
    <div style="background-color:#ffffff;padding:20px;border-radius:5px;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                <th align="left">{{ gp247_language_render('email.order.sku') }}</th>
                <th align="left">{{ gp247_language_render('email.order.name') }}</th>
                <th align="right">{{ gp247_language_render('email.order.qty') }}</th>
                <th align="right">{{ gp247_language_render('email.order.total') }}</th>
            </tr>
            @foreach ($lines as $line)
            <tr>
                <td>{{ $line['sku'] }}</td>
                <td>{{ $line['name'] }}</td>
                <td align="right">{{ rtrim(rtrim(number_format($line['qty'], 2, '.', ''), '0'), '.') }}</td>
                <td align="right">{{ $line['total'] }}</td>
            </tr>
            @endforeach
        </table>
        <p style="text-align:right;font-size:1.2em;margin-top:15px;"><strong>{{ gp247_language_render('order.total') }}:</strong> {{ $total }}</p>
    </div>
    @if ($orderUrl !== '')
    <p style="text-align:center;margin-top:20px;"><a href="{{ $orderUrl }}" style="display:inline-block;padding:10px 18px;background:#2c3e50;color:#fff;text-decoration:none;border-radius:4px;">{{ gp247_language_render('multi_vendor.mail.order_created.button') }}</a></p>
    @endif
</div>
@endsection
