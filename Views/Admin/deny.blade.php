@extends($templatePathAdminVendor.'layout')

@section('main')

<x-gp247::card>
    <div class="py-10 text-center">
        <h2 class="text-xl font-semibold text-red-600 dark:text-red-400">
            403 - {{ gp247_language_render('admin.deny_content') }}
        </h2>
        @if ($url)
        <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
            <i class="fa fa-warning text-red-500"></i>
            {{ gp247_language_render('admin.deny_msg') }}
        </p>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            <strong>URL:</strong> <code class="rounded bg-gray-100 px-1 py-0.5 dark:bg-gray-700">{{ $url }}</code>
            &nbsp;<strong>Method:</strong> <code class="rounded bg-gray-100 px-1 py-0.5 dark:bg-gray-700">{{ $method }}</code>
        </p>
        @endif
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
