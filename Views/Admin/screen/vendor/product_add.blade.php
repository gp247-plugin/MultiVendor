@extends($templatePathAdminVendor.'layout')

@section('main')

@include($templatePathAdminVendor.'screen.vendor.product_form', [
    'product' => $product,
    'productKind' => $product_kind,
    'urlAction' => gp247_route_admin('vendor_admin_product.create'),
])

@endsection
