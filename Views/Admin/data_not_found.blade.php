@extends($templatePathAdminVendor.'layout')

@section('main')

<x-gp247::card>
    <div class="py-10 text-center">
        <p class="text-base font-medium text-gray-700 dark:text-gray-200">
            <i class="fas fa-exclamation text-red-500"></i>
            {{ gp247_language_render('admin.data_not_found_msg') }}
        </p>
        <p class="mt-3 text-sm text-red-600 dark:text-red-400">{{ $url }}</p>
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
