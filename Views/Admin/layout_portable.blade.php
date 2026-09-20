{{--
    Standalone vendor auth shell (login / register / forgot / reset).

    A centred card with no sidebar or header, mirroring core's own auth layout.
    Loads the same TailAdmin assets as the main vendor shell; the v1 AdminLTE +
    jQuery bundle it used is gone in core 2.x.

    Sections: @yield('main'); stacks: @stack('styles') / @stack('scripts').
    Variables: $title.
--}}
<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ gp247_config_admin('ADMIN_TITLE') }} | {{ $title ?? '' }}</title>
    <link rel="icon" href="{{ gp247_file(gp247_store_info('icon')) }}" type="image/png" sizes="16x16">

    <script>
        try {
            if (localStorage.getItem('gp247-theme') === 'dark') {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {}
    </script>

    <link rel="stylesheet" href="{{ gp247_file('GP247/Core/AdminShell/vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ gp247_file('GP247/Core/AdminShell/css/admin.css') }}">
    <link rel="stylesheet" href="{{ gp247_file('GP247/Plugins/MultiVendor/css/admin.css') }}">

    <script src="{{ gp247_file('GP247/Core/AdminShell/js/admin.js') }}"></script>
    @livewireStyles
    @stack('styles')
</head>

<body class="mvp-body">
<div class="flex min-h-screen items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-8">
            <a href="{{ gp247_route_admin('vendor_admin.home') }}" class="mb-6 flex justify-center">
                <img src="{{ gp247_file(gp247_store_info('logo')) }}" alt="logo" class="max-h-16 w-auto max-w-[200px]">
            </a>

            <h2 class="mb-6 text-center text-lg font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-100">
                {{ $title ?? '' }}
            </h2>

            @yield('main')
        </div>
    </div>
</div>

<x-gp247::toast />
@include($templatePathAdminVendor.'component.alerts')

@livewireScripts
<script>window.mvpCsrfToken = '{{ csrf_token() }}';</script>
<script src="{{ gp247_file('GP247/Plugins/MultiVendor/js/mvp-admin.js') }}"></script>
@stack('scripts')
</body>
</html>
