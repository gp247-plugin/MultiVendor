@extends($templatePathAdminVendor.'layout')

@section('main')

<x-gp247::card>
    <div class="py-10 text-center">
        <h2 class="text-xl font-semibold text-red-600 dark:text-red-400">
            {{ gp247_language_render('multi_vendor.account_inactive_title') }}
        </h2>
        <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
            <i class="fa fa-warning text-red-500"></i>
            {{ gp247_language_render('multi_vendor.account_inactive_msg') }}
        </p>
    </div>
</x-gp247::card>

@endsection

@push('scripts')
@if ($url)
<script>
    window.history.pushState('', '', @js($url));
</script>
@endif
@endpush
