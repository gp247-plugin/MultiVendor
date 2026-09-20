{{--
    Vendor admin sidebar (TailAdmin).

    Same menu the v1 AdminLTE sidebar rendered — the vendor menu is hardcoded here
    (it is not part of the staff AdminMenu tree) — restyled with the shell's
    utilities and driven by the Alpine `gp247` store for the mobile slide-in.
    Collapsible groups use a local Alpine `open` flag; active state still comes
    from AdminMenu::checkUrlIsChild.
--}}
@php
    $linkBase = 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition';
    $linkIdle = 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white';
    $linkActive = 'bg-blue-50 font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-200';
    $sectionLabel = 'px-3 pb-1 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500';

    $current = url()->current();
    $isActive = fn(string $url) => \GP247\Core\Models\AdminMenu::checkUrlIsChild($current, $url);

    // Pro screens stay in the sidebar on a Free marketplace: locked, and pointing
    // at the gateway that explains them (US-multi-vendor-pro-upgrade-funnel). A
    // shop owner should know the feature exists even when it is not switched on.
    $catalogue = \App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::class;
    $proItem = function (string $slug) use ($catalogue, $isActive) {
        $screen = $catalogue::screen($slug);
        $open = $screen !== null && $catalogue::isOpen($screen);
        $url = $open
            ? gp247_route_admin($screen['route'])
            : $catalogue::gatewayUrl($catalogue::VENDOR, $slug);

        return ['url' => $url, 'locked' => !$open, 'active' => $isActive($url)];
    };
@endphp

<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-30 flex w-64 transform flex-col border-r border-gray-200 bg-white transition-all duration-200 dark:border-gray-700 dark:bg-gray-800 lg:static lg:translate-x-0"
>
    <a href="{{ gp247_route_admin('vendor_admin.home') }}"
        class="flex h-16 shrink-0 flex-col items-center justify-center border-b border-gray-200 px-5 dark:border-gray-700">
        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
            Vendor
        </span>
        <span class="text-xs font-light text-gray-500 dark:text-gray-400">Admin</span>
    </a>

    <nav class="flex-1 space-y-4 overflow-y-auto px-3 py-4">
        @if (vendor()->user()->status)

            {{-- Order search, mobile only (the header carries it from `sm` up). --}}
            <form action="{{ gp247_route_admin('vendor_admin_order.index') }}" method="get" class="flex items-center sm:hidden">
                <input name="keyword" type="search"
                    class="block w-full rounded-s-lg border border-e-0 border-gray-300 px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    placeholder="{{ gp247_language_render('admin.order.search') }}"
                    aria-label="{{ gp247_language_render('admin.order.search') }}">
                <button type="submit" class="rounded-e-lg bg-blue-600 px-3 py-1.5 text-sm text-white transition hover:bg-blue-700">
                    <i class="fas fa-search"></i>
                </button>
            </form>

            {{-- Shop --}}
            <div>
                <p class="{{ $sectionLabel }}">
                    <i class="fab fa-shopify"></i> {{ gp247_language_render('admin.menu_titles.ADMIN_SHOP') }}
                </p>
                <ul class="space-y-1">
                    @php $orderActive = $isActive(gp247_route_admin('vendor_admin_order.index')); @endphp
                    <li x-data="{ open: {{ $orderActive ? 'true' : 'false' }} }">
                        <button type="button" x-on:click="open = ! open"
                            class="{{ $linkBase }} {{ $orderActive ? $linkActive : $linkIdle }} w-full justify-between">
                            <span class="flex items-center gap-3">
                                <i class="fas fa-shopping-basket w-5 text-center"></i>
                                {{ gp247_language_render('admin.menu_titles.ADMIN_SHOP_ORDER') }}
                            </span>
                            <i class="fas fa-angle-left transition-transform" :class="open && 'rotate-[-90deg]'"></i>
                        </button>
                        <ul class="mt-1 space-y-1 ps-4" x-show="open" x-cloak>
                            <li>
                                <a href="{{ gp247_route_admin('vendor_admin_order.index') }}"
                                    class="{{ $linkBase }} {{ $orderActive ? $linkActive : $linkIdle }}">
                                    <i class="fas fa-shopping-cart w-5 text-center"></i>
                                    {{ gp247_language_render('admin.menu_titles.order') }}
                                </a>
                            </li>
                        </ul>
                    </li>

                    @php
                        $catalogLinks = [
                            ['url' => gp247_route_admin('vendor_admin_category.index'), 'icon' => 'fas fa-folder-open', 'label' => gp247_language_render('admin.menu_titles.category')],
                            ['url' => gp247_route_admin('vendor_admin_product.index'), 'icon' => 'far fa-file-image', 'label' => gp247_language_render('admin.menu_titles.product')],
                            ['url' => gp247_route_admin('vendor_admin_supplier.index'), 'icon' => 'fas fa-user-secret', 'label' => gp247_language_render('admin.menu_titles.supplier')],
                        ];
                        $catalogActive = collect($catalogLinks)->contains(fn($l) => $isActive($l['url']));
                    @endphp
                    <li x-data="{ open: {{ $catalogActive ? 'true' : 'false' }} }">
                        <button type="button" x-on:click="open = ! open"
                            class="{{ $linkBase }} {{ $catalogActive ? $linkActive : $linkIdle }} w-full justify-between">
                            <span class="flex items-center gap-3">
                                <i class="fas fa-folder-open w-5 text-center"></i>
                                {{ gp247_language_render('admin.menu_titles.ADMIN_SHOP_CATALOG') }}
                            </span>
                            <i class="fas fa-angle-left transition-transform" :class="open && 'rotate-[-90deg]'"></i>
                        </button>
                        <ul class="mt-1 space-y-1 ps-4" x-show="open" x-cloak>
                            @foreach ($catalogLinks as $link)
                            <li>
                                <a href="{{ $link['url'] }}" class="{{ $linkBase }} {{ $isActive($link['url']) ? $linkActive : $linkIdle }}">
                                    <i class="{{ $link['icon'] }} w-5 text-center"></i>
                                    {{ $link['label'] }}
                                </a>
                            </li>
                            @endforeach
                        </ul>
                    </li>

                    <li>
                        <a href="{{ gp247_route_admin('vendor_admin_payment.index') }}"
                            class="{{ $linkBase }} {{ $isActive(gp247_route_admin('vendor_admin_payment.index')) ? $linkActive : $linkIdle }}">
                            <i class="far fa-money-bill-alt w-5 text-center"></i>
                            {{ gp247_language_render('multi_vendor.vendor_payment') }}
                        </a>
                    </li>
                    @php ($pro = $proItem('kyc'))
                    <li>
                        <a href="{{ $pro['url'] }}"
                            class="{{ $linkBase }} {{ $pro['active'] ? $linkActive : $linkIdle }}"
                            data-testid="multi-vendor-kyc-menu">
                            <i class="fas fa-id-card w-5 text-center"></i>
                            <span class="flex-1">{{ gp247_language_render('multi_vendor.kyc.title') }}</span>
                            @if ($pro['locked'])
                                <i class="fas fa-lock text-xs text-gray-400" data-testid="multi-vendor-pro-lock-kyc"></i>
                            @endif
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Content --}}
            <div class="border-t border-gray-100 pt-4 dark:border-gray-700">
                <p class="{{ $sectionLabel }}">
                    <i class="fas fa-file-signature"></i> {{ gp247_language_render('admin.menu_titles.ADMIN_CONTENT') }}
                </p>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ gp247_route_admin('vendor_admin_banner.index') }}"
                            class="{{ $linkBase }} {{ $isActive(gp247_route_admin('vendor_admin_banner.index')) ? $linkActive : $linkIdle }}">
                            <i class="fas fa-image w-5 text-center"></i>
                            {{ gp247_language_render('admin.menu_titles.banner') }}
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Store settings --}}
            <div class="border-t border-gray-100 pt-4 dark:border-gray-700">
                <p class="{{ $sectionLabel }}">
                    <i class="fas fa-store-alt"></i> {{ gp247_language_render('admin.menu_titles.ADMIN_SHOP_SETTING') }}
                </p>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ gp247_route_admin('vendor_admin_store.index') }}"
                            class="{{ $linkBase }} {{ $isActive(gp247_route_admin('vendor_admin_store.index')) ? $linkActive : $linkIdle }}">
                            <i class="fab fa-shirtsinbulk w-5 text-center"></i>
                            {{ gp247_language_render('admin.store.title') }}
                        </a>
                    </li>
                    @php ($pro = $proItem('my-plan'))
                    <li>
                        <a href="{{ $pro['url'] }}"
                            class="{{ $linkBase }} {{ $pro['active'] ? $linkActive : $linkIdle }}"
                            data-testid="multi-vendor-plan-select-menu">
                            <i class="fas fa-layer-group w-5 text-center"></i>
                            <span class="flex-1">{{ gp247_language_render('multi_vendor.plan_self.title') }}</span>
                            @if ($pro['locked'])
                                <i class="fas fa-lock text-xs text-gray-400" data-testid="multi-vendor-pro-lock-my-plan"></i>
                            @endif
                        </a>
                    </li>
                    @php ($pro = $proItem('order-create'))
                    <li>
                        <a href="{{ $pro['url'] }}"
                            class="{{ $linkBase }} {{ $pro['active'] ? $linkActive : $linkIdle }}"
                            data-testid="multi-vendor-order-create-menu">
                            <i class="fas fa-file-invoice w-5 text-center"></i>
                            <span class="flex-1">{{ gp247_language_render('multi_vendor.order_create.title') }}</span>
                            @if ($pro['locked'])
                                <i class="fas fa-lock text-xs text-gray-400" data-testid="multi-vendor-pro-lock-order-create"></i>
                            @endif
                        </a>
                    </li>
                    @php ($pro = $proItem('customer-groups'))
                    <li>
                        <a href="{{ $pro['url'] }}"
                            class="{{ $linkBase }} {{ $pro['active'] ? $linkActive : $linkIdle }}"
                            data-testid="multi-vendor-customer-groups-menu">
                            <i class="fas fa-user-tag w-5 text-center"></i>
                            <span class="flex-1">{{ gp247_language_render('multi_vendor.groups.title') }}</span>
                            @if ($pro['locked'])
                                <i class="fas fa-lock text-xs text-gray-400" data-testid="multi-vendor-pro-lock-customer-groups"></i>
                            @endif
                        </a>
                    </li>
                    @php ($pro = $proItem('reviews'))
                    <li>
                        <a href="{{ $pro['url'] }}"
                            class="{{ $linkBase }} {{ $pro['active'] ? $linkActive : $linkIdle }}"
                            data-testid="multi-vendor-vendor-reviews-menu">
                            <i class="far fa-comments w-5 text-center"></i>
                            <span class="flex-1">{{ gp247_language_render('multi_vendor.reviews.title') }}</span>
                            @if ($pro['locked'])
                                <i class="fas fa-lock text-xs text-gray-400" data-testid="multi-vendor-pro-lock-reviews"></i>
                            @endif
                        </a>
                    </li>
                    @php ($pro = $proItem('plugins'))
                    <li>
                        <a href="{{ $pro['url'] }}"
                            class="{{ $linkBase }} {{ $pro['active'] ? $linkActive : $linkIdle }}"
                            data-testid="multi-vendor-vendor-plugins-menu">
                            <i class="fas fa-plug w-5 text-center"></i>
                            <span class="flex-1">{{ gp247_language_render('multi_vendor.vendor_plugins.title') }}</span>
                            @if ($pro['locked'])
                                <i class="fas fa-lock text-xs text-gray-400" data-testid="multi-vendor-pro-lock-plugins"></i>
                            @endif
                        </a>
                    </li>
                </ul>
            </div>

        @endif
    </nav>
</aside>
