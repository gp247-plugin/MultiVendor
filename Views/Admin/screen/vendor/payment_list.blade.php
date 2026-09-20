@extends($templatePathAdminVendor.'layout')

@push('styles')
    {!! $css ?? '' !!}
@endpush

@push('scripts')
    {!! $js ?? '' !!}
@endpush

@section('main')

@if (count($dataAmount))
    @include($templatePathAdminVendor.'component.payment_summary')
@endif

@include($templatePathAdminVendor.'component.grid')

@endsection
