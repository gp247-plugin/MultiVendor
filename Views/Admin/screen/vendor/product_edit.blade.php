@extends($templatePathAdminVendor.'layout')

@section('main')

@include($templatePathAdminVendor.'screen.vendor.product_form', [
    'product' => $product,
    'productKind' => $product->kind,
    'urlAction' => gp247_route_admin('vendor_admin_product.edit', ['id' => $product['id']]),
])

@endsection
