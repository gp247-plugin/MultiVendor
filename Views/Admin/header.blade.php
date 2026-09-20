{{--
    Vendor admin top bar (TailAdmin).

    Same affordances as the v1 AdminLTE navbar — sidebar toggle, language switch,
    order search, storefront link, profile menu — plus the shell's dark-mode
    switch. Bootstrap's `data-toggle="dropdown"` is replaced by Alpine.
--}}
@php
    $vendorUser = vendor()->user();
    $vendorAvatar = ($vendorUser && $vendorUser->avatar)
        ? gp247_file($vendorUser->avatar)
        : gp247_file('GP247/Core/avatar/user.jpg');

    $languages = gp247_language_all();
    $currentLocale = session('locale') ?? app()->getLocale();
    $currentLanguage = $languages[$currentLocale] ?? null;

    $iconButton = 'rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700';
@endphp

<header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 dark:border-gray-700 dark:bg-gray-800 sm:px-6">

    <div class="flex items-center gap-3">
        <button type="button" x-on:click="$store.gp247.toggleSidebar()"
            class="{{ $iconButton }}"
            aria-label="{{ gp247_language_render('admin.toggle_sidebar') }}">
            <i class="fas fa-bars"></i>
        </button>

        <form action="{{ gp247_route_admin('vendor_admin_order.index') }}" method="get" class="hidden items-center sm:flex">
            <input name="keyword" type="search"
                class="block w-56 rounded-s-lg border border-e-0 border-gray-300 px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                placeholder="{{ gp247_language_render('admin.order.search') }}"
                aria-label="{{ gp247_language_render('admin.order.search') }}">
            <button type="submit" class="rounded-e-lg bg-blue-600 px-3 py-1.5 text-sm text-white transition hover:bg-blue-700">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>

    <div class="flex items-center gap-2">
        <a href="{{ gp247_vendor_get_url(session('adminStoreId')) }}" target="_blank" rel="noopener"
            class="{{ $iconButton }}" title="{{ gp247_language_render('admin.go_to_shop') }}">
            <i class="fas fa-store-alt"></i>
        </a>

        @if ($languages && count($languages) > 1)
        <div class="relative" x-data="{ open: false }">
            <button type="button" x-on:click="open = ! open"
                class="flex items-center gap-1 rounded-lg px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                aria-label="{{ gp247_language_render('admin.language.title') }}">
                @if ($currentLanguage)
                    <img src="{{ gp247_file($currentLanguage['icon']) }}" alt="{{ $currentLanguage['name'] }}" class="h-5 w-7 rounded object-cover">
                @else
                    <i class="fas fa-globe"></i>
                @endif
                <i class="fas fa-angle-down text-xs"></i>
            </button>

            <div x-show="open" x-on:click.outside="open = false" x-cloak
                class="absolute end-0 mt-2 w-44 rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                @foreach ($languages as $code => $language)
                    <a href="{{ gp247_route_admin('vendor_admin.locale', ['code' => $code]) }}"
                        class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700 {{ $code === $currentLocale ? 'bg-gray-50 font-semibold dark:bg-gray-700/50' : '' }}">
                        <img src="{{ gp247_file($language['icon']) }}" alt="{{ $language['name'] }}" class="h-5 w-7 rounded object-cover">
                        {{ $language['name'] }}
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        <button type="button" x-on:click="$store.gp247.toggleTheme()"
            class="{{ $iconButton }}" aria-label="{{ gp247_language_render('admin.toggle_theme') }}">
            <i class="fas" :class="dark ? 'fa-sun' : 'fa-moon'"></i>
        </button>

        {{-- Profile menu --}}
        <div class="relative" x-data="{ open: false }">
            <button type="button" x-on:click="open = ! open" class="flex items-center gap-2 rounded-lg p-1 hover:bg-gray-100 dark:hover:bg-gray-700">
                <img src="{{ $vendorAvatar }}" alt="{{ $vendorUser->name ?? '' }}" class="h-8 w-8 rounded-full object-cover">
                <i class="fas fa-angle-down text-xs text-gray-500 dark:text-gray-400"></i>
            </button>

            <div x-show="open" x-on:click.outside="open = false" x-cloak
                class="absolute end-0 mt-2 w-64 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-col items-center gap-1 border-b border-gray-100 px-4 py-4 dark:border-gray-700">
                    <img src="{{ $vendorAvatar }}" alt="{{ $vendorUser->name ?? '' }}" class="h-16 w-16 rounded-full object-cover">
                    <span class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $vendorUser->name ?? '' }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ gp247_language_render('admin.user.member_since') }} {{ $vendorUser->created_at ?? '' }}
                    </span>
                </div>

                <div class="flex items-center justify-between gap-2 px-4 py-3">
                    <x-gp247::button size="sm" variant="secondary" href="{{ gp247_route_admin('vendor.setting') }}">
                        {{ gp247_language_render('admin.user.setting') }}
                    </x-gp247::button>
                    <x-gp247::button size="sm" variant="secondary" href="{{ gp247_route_admin('vendor.logout') }}">
                        {{ gp247_language_render('admin.user.logout') }}
                    </x-gp247::button>
                </div>
            </div>
        </div>
    </div>
</header>
