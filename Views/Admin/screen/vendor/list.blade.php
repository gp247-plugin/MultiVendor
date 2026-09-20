@extends($templatePathAdminVendor.'layout')

@push('styles')
    {!! $css ?? '' !!}
@endpush

@push('scripts')
    {!! $js ?? '' !!}
@endpush

@section('main')

@include($templatePathAdminVendor.'component.grid')

@endsection
