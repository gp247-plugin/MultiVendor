{{--
    Vendor admin shell (TailAdmin).

    The vendor area is its own mini-admin: it authenticates against the `vendor`
    guard and carries its own menu, so it cannot simply extend
    gp247-admin::layouts.admin (that layout renders the staff AdminMenu for the
    `admin` guard). It therefore mirrors that layout's structure while keeping the
    vendor's own sidebar/header.

    It reuses core's published admin-shell assets (Tailwind base + the Alpine
    `gp247` store that drives dark mode and the sidebar) and adds the plugin's own
    Tailwind bundle for classes core does not ship. The v1 AdminLTE/jQuery bundle
    it used to load (GP247/Core/LTE/**) no longer exists in core 2.x.

    Sections: @yield('main'); stacks: @stack('styles') / @stack('scripts').
    Variables: $title, $subTitle, $icon, $breadcrumb, $more_info.
--}}
<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? '' }}</title>
    <link rel="icon" href="{{ gp247_file(gp247_store_info('icon')) }}" type="image/png" sizes="16x16">

    {{-- Apply the persisted theme before first paint to avoid a light/dark flash. --}}
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
    <link rel="stylesheet" href="{{ gp247_file('GP247/Plugins/MultiVendor/css/vendor-theme.css') }}">
    @include($templatePathAdminVendor.'component.css')

    {{-- admin.js registers its `alpine:init` listener, so it must load before
         Livewire boots Alpine at @livewireScripts. --}}
    <script src="{{ gp247_file('GP247/Core/AdminShell/js/admin.js') }}"></script>
    @livewireStyles
    @stack('styles')
</head>

<body class="mvp-body">
<div
    x-data="{
        get sidebarOpen() { return $store.gp247.sidebarOpen },
        get sidebarCollapsed() { return $store.gp247.sidebarCollapsed },
        get dark() { return $store.gp247.dark },
    }"
    class="min-h-screen lg:flex"
>
    @include($templatePathAdminVendor.'sidebar')

    {{-- Backdrop shown when the sidebar is open on small screens. --}}
    <div
        x-show="sidebarOpen"
        x-on:click="$store.gp247.toggleSidebar()"
        class="fixed inset-0 z-20 bg-gray-900/50 lg:hidden"
        x-cloak
    ></div>

    <div class="flex min-h-screen flex-1 flex-col">
        @include($templatePathAdminVendor.'header')

        <main class="flex-1 p-4 sm:p-6">
            <div class="mb-5">
                <h1 class="flex flex-wrap items-center gap-2 text-xl font-semibold text-gray-800 dark:text-gray-100">
                    @if (!empty($icon))<i class="{{ $icon }} text-gray-400"></i>@endif
                    {!! $title ?? '' !!}
                    @if (!empty($subTitle))
                        <span class="rounded border border-dashed border-gray-300 px-2 py-0.5 text-sm font-normal text-gray-500 dark:border-gray-600 dark:text-gray-400">{!! $subTitle !!}</span>
                    @endif
                </h1>

                @if (!empty($more_info))
                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">{!! $more_info !!}</div>
                @endif

                <nav class="mt-2 flex flex-wrap items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                    <a href="{{ gp247_route_admin('vendor_admin.home') }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                        <i class="fa fa-home"></i> {{ gp247_language_render('admin.home') }}
                    </a>
                    @if (!empty($breadcrumb))
                        <span>/</span>
                        <a href="{{ $breadcrumb['url'] }}" class="hover:text-blue-600 dark:hover:text-blue-400">{{ $breadcrumb['name'] }}</a>
                    @endif
                    <span>/</span>
                    <span class="text-gray-700 dark:text-gray-200">{!! $title ?? '' !!}</span>
                </nav>
            </div>

            {{-- Full-page Livewire components render into $slot; legacy @extends
                 blades render into @yield('main'). Supporting both keeps the shell
                 shared during the vendor-admin Livewire migration (Pha 2). --}}
            {{ $slot ?? '' }}
            @yield('main')
        </main>

        @include($templatePathAdminVendor.'footer')
    </div>

    {{-- Global, event-based UI feedback (ADR-005) — the same toast channel
         mvp-admin.js and Livewire components dispatch into. --}}
    <x-gp247::toast />
    @include($templatePathAdminVendor.'component.alerts')
</div>

@livewireScripts
<script>window.mvpCsrfToken = '{{ csrf_token() }}';</script>
<script src="{{ gp247_file('GP247/Plugins/MultiVendor/js/mvp-admin.js') }}"></script>
@include($templatePathAdminVendor.'component.script')
@stack('scripts')
</body>
</html>
