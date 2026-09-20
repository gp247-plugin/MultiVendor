{{--
    Shop header shared by every /vendor/{code}/* page (S2-5, Shopee-style):
    cover (og_image), avatar (logo), name, short description, metrics
    (sellable products · seller rating from ProductRating when enabled · member
    since), contact line, tab navigation and the Pro quick-order button.

    Uses only classes the GP247Front bundle already ships (rule gp247.md §3b);
    anything the bundle has no utility for (fixed heights, ring, clamp, gradient)
    is written as an inline style rather than a class that would silently do
    nothing.

    Variables: $store (AdminStore), $stats (ShopPageService::stats),
               $storeCode, $tab (active tab key), $storeId.

    @aidlc-unit multi-vendor-pro
    @aidlc-story US-multi-vendor-pro-storefront-shop-page
    @aidlc-adr multi-vendor_storefront-shop-page
--}}
@php
    $t = fn (string $k, array $v = []) => gp247_language_render('multi_vendor.shop.'.$k, $v);
    $shopUrl = gp247_route_front('MultiVendor.detail', ['code' => $storeCode]);
    $tab = $tab ?? \App\GP247\Plugins\MultiVendor\Storefront\ShopPageService::TAB_PRODUCTS;
    $tabs = [
        'products' => $t('tab_products'),
        'reviews' => $t('tab_reviews'),
        'info' => $t('tab_info'),
    ];
    if (empty($stats['ratingEnabled'])) {
        unset($tabs['reviews']);
    }
    // A count beside the label tells a shopper whether a tab is worth opening.
    $tabCounts = [
        'products' => (int) ($stats['products'] ?? 0),
        'reviews' => (int) ($stats['rating']['total'] ?? 0),
    ];
    $storeName = $store->getTitle() ?: $store->code;
    // Cover image: the store's own banner (type ShopPageService::VENDOR_BANNER_TYPE,
    // the "Banner store" the platform already ships and every store is seeded
    // with). A vendor changes their cover by managing that banner, which is the
    // one place they already go for shop imagery — og_image is the social-sharing
    // picture and only stands in when no banner is set.
    $coverBanner = isset($banners) && $banners->isNotEmpty()
        ? $banners->first()
        : \App\GP247\Plugins\MultiVendor\Storefront\ShopPageService::banners($store->id, 1)->first();
    $coverImage = $coverBanner->image ?? ($store->og_image ?: '');
    $description = trim(strip_tags((string) $store->getDescription()));
    $ratingMax = function_exists('gp247_product_rating_max') ? gp247_product_rating_max($store->id) : 5;
    // The quick-order screens ship with the paid plugin; the button shows only when
    // the edition allows it, the marketplace switched it on, and the route exists.
    $quickOrder = \App\GP247\Plugins\MultiVendor\Tier\Tier::allows(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_QUICK_ORDER)
        && gp247_config_global('MultiVendor_quick_order') && gp247_config_global('MultiVendor')
        && \Illuminate\Support\Facades\Route::has('MultiVendor.quick_order');
@endphp

<section class="card overflow-hidden mb-6" data-testid="multi-vendor-shop-header">
    {{-- Cover. A shop without one still gets a band of colour rather than a grey
         gap, so the avatar always has something to sit against. --}}
    <div class="relative" style="height: 240px; background: linear-gradient(120deg, #0f172a 0%, #0284c7 55%, #38bdf8 100%);" data-testid="multi-vendor-shop-cover">
        @if ($coverImage !== '')
            @if (!empty($coverBanner->url))
                <a href="{{ $coverBanner->url }}" target="{{ $coverBanner->target ?: '_self' }}" class="block w-full h-full">
                    <img src="{{ gp247_file($coverImage) }}" alt="{{ $coverBanner->name ?? $storeName }}" class="w-full h-full object-cover">
                </a>
            @else
                <img src="{{ gp247_file($coverImage) }}" alt="{{ $storeName }}" class="w-full h-full object-cover">
            @endif
        @endif
        <span class="absolute inset-0 pointer-events-none" style="background: linear-gradient(180deg, rgba(15,23,42,0) 45%, rgba(15,23,42,.45) 100%);"></span>
    </div>

    <div class="px-4" style="padding-bottom: 1rem;">
        <div class="flex flex-wrap items-end gap-4">
            {{-- Avatar --}}
            {{-- relative: the cover above is positioned, so a static avatar would
                 paint underneath it and lose its top edge. --}}
            <div class="relative shrink-0 rounded-full overflow-hidden bg-white"
                style="width: 104px; height: 104px; margin-top: -52px; border: 4px solid #fff; box-shadow: 0 6px 18px rgba(15,23,42,.18);">
                @if (!empty($store->logo))
                    {{-- object-contain, not cover: a shop logo is usually a wide
                         wordmark, and cropping it to a circle cuts the name in half. --}}
                    <img src="{{ gp247_file($store->logo) }}" alt="{{ $storeName }}" class="w-full h-full object-contain" style="padding: 6px;">
                @else
                    <div class="w-full h-full bg-ink-50 flex items-center justify-center font-bold text-ink-400" style="font-size: 2.25rem;">{{ mb_substr($storeName, 0, 1) }}</div>
                @endif
            </div>

            {{-- min-width instead of a breakpoint class: the bundle ships no
                 responsive flex-direction utility, and a wrap threshold does the
                 same job — on a phone the name drops to its own line rather than
                 being squeezed to one word per line beside the avatar. --}}
            <div class="flex-1 mt-3" style="min-width: 240px;">
                <h1 class="text-2xl font-bold text-ink-900 flex flex-wrap items-center gap-2" data-testid="multi-vendor-shop-name">{{ $storeName }}
                    @if (!empty($stats['verified']))
                        <span class="inline-flex items-center gap-1 rounded-full bg-brand-600 text-white text-[10px] font-bold px-2 py-1" title="{{ gp247_language_render('multi_vendor.kyc.verified') }}" data-testid="multi-vendor-shop-verified">
                            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>{{ gp247_language_render('multi_vendor.kyc.verified') }}
                        </span>
                    @endif
                </h1>
                @if ($description !== '')
                    <p class="text-sm text-ink-500 mt-1" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $description }}</p>
                @endif
            </div>

            @if ($quickOrder)
                <a href="{{ gp247_route_front('MultiVendor.quick_order', ['code' => $storeCode]) }}" class="btn-primary"
                    data-testid="multi-vendor-shop-quick-order">{{ gp247_language_render('multi_vendor.quick_order') }}</a>
            @endif
        </div>

        {{-- Metrics as tiles: the number is what a shopper compares shops on, so it
             leads and the label explains it, instead of both running together in a
             sentence. --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-4">
            <div class="rounded-xl bg-ink-50 px-4 py-3" data-testid="multi-vendor-shop-products-count">
                <div class="text-lg font-bold text-ink-900">{{ number_format((int) ($stats['products'] ?? 0)) }}</div>
                <div class="text-xs text-ink-500">{{ $t('products_count') }}</div>
            </div>

            <div class="rounded-xl bg-ink-50 px-4 py-3" data-testid="multi-vendor-shop-rating">
                @if (!empty($stats['ratingEnabled']) && !empty($stats['rating']))
                    <div class="text-lg font-bold text-brand-600 flex items-center gap-1">
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 15l-5.9 3.1 1.1-6.6L.4 6.9l6.6-1L10 0l3 5.9 6.6 1-4.8 4.6 1.1 6.6z"/></svg>
                        {{ $stats['rating']['average'] }}<span class="text-sm font-medium text-ink-400">/ {{ $ratingMax }}</span>
                    </div>
                    <div class="text-xs text-ink-500">{{ $t('rating_count', ['count' => (int) $stats['rating']['total']]) }}</div>
                @else
                    <div class="text-lg font-bold text-ink-300">—</div>
                    <div class="text-xs text-ink-500">{{ $t('no_rating') }}</div>
                @endif
            </div>

            <div class="rounded-xl bg-ink-50 px-4 py-3" data-testid="multi-vendor-shop-joined">
                <div class="text-lg font-bold text-ink-900">{{ !empty($stats['joined']) ? $stats['joined']->format('m/Y') : '—' }}</div>
                <div class="text-xs text-ink-500">{{ $t('joined') }}</div>
            </div>
        </div>

        {{-- S5-3: the shop's recent record. Absent entirely when the shop has not
             sold enough for the figures to mean anything (TrustSignals::MIN_ORDERS). --}}
        @if (!empty($stats['trust']))
            @php($trust = $stats['trust'])
            <div class="mt-3 pt-3 border-t border-ink-100" data-testid="multi-vendor-shop-trust">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-ink-700">
                    <div data-testid="multi-vendor-shop-dispute-rate">
                        <span class="font-semibold text-ink-900">{{ $trust['dispute_rate'] }}%</span>
                        {{ $t('trust.dispute_rate') }}
                    </div>

                    @if ($trust['handling_hours'] !== null)
                        <div data-testid="multi-vendor-shop-handling-time">
                            <span class="font-semibold text-ink-900">{{ $trust['handling_hours'] < 48 ? $trust['handling_hours'].'h' : round($trust['handling_hours'] / 24, 1).'d' }}</span>
                            {{ $t('trust.handling_time') }}
                        </div>
                    @endif

                    @if ($trust['dispute_response_rate'] !== null)
                        <div data-testid="multi-vendor-shop-response-rate">
                            <span class="font-semibold text-ink-900">{{ $trust['dispute_response_rate'] }}%</span>
                            {{ $t('trust.response_rate') }}
                        </div>
                    @endif

                    @if ($trust['review_reply_rate'] !== null)
                        <div data-testid="multi-vendor-shop-review-reply-rate">
                            <span class="font-semibold text-ink-900">{{ $trust['review_reply_rate'] }}%</span>
                            {{ $t('trust.review_reply_rate') }}
                        </div>
                    @endif
                </div>
                <div class="text-xs text-ink-400 mt-1" data-testid="multi-vendor-shop-trust-window">
                    {{ $t('trust.window', ['days' => (int) $trust['window_days'], 'orders' => number_format((int) $trust['orders'])]) }}
                </div>
            </div>
        @endif

        {{-- Contact --}}
        @if (!empty($store->phone) || !empty($store->email) || !empty($store->address))
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-3 text-xs text-ink-500" data-testid="multi-vendor-shop-contact">
                @if (!empty($store->phone))
                    <span class="inline-flex items-center gap-1">
                        <svg class="w-4 h-4 text-ink-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M2 3.5A1.5 1.5 0 013.5 2h1.6a1.5 1.5 0 011.45 1.11l.7 2.6a1.5 1.5 0 01-.4 1.46l-1 1a11.5 11.5 0 004.98 4.98l1-1a1.5 1.5 0 011.46-.4l2.6.7A1.5 1.5 0 0118 14.9v1.6a1.5 1.5 0 01-1.5 1.5A14.5 14.5 0 012 3.5z"/></svg>
                        {{ $store->phone }}
                    </span>
                @endif
                @if (!empty($store->email))
                    <span class="inline-flex items-center gap-1">
                        <svg class="w-4 h-4 text-ink-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M2 5.5A1.5 1.5 0 013.5 4h13A1.5 1.5 0 0118 5.5v.2l-8 4.3-8-4.3v-.2z"/><path d="M18 7.6v6.9a1.5 1.5 0 01-1.5 1.5h-13A1.5 1.5 0 012 14.5V7.6l7.6 4.1a1 1 0 00.8 0L18 7.6z"/></svg>
                        {{ $store->email }}
                    </span>
                @endif
                @if (!empty($store->address))
                    <span class="inline-flex items-center gap-1">
                        <svg class="w-4 h-4 text-ink-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18s6-5.3 6-9.3A6 6 0 004 8.7C4 12.7 10 18 10 18zm0-7.3a2 2 0 110-4 2 2 0 010 4z" clip-rule="evenodd"/></svg>
                        {{ $store->address }}
                    </span>
                @endif
            </div>
        @endif
    </div>

    {{-- Tabs. The active one is marked by a bar that sits on the divider itself
         (hence the negative margin), so the panel below reads as belonging to the
         selected tab rather than as a separate box. --}}
    <nav class="flex flex-wrap border-t border-ink-100 px-2" data-testid="multi-vendor-shop-tabs">
        @foreach ($tabs as $key => $label)
            @php($isActive = $tab === $key)
            <a href="{{ $shopUrl }}{{ $key === 'products' ? '' : '?tab='.$key }}"
                class="inline-flex items-center gap-2 px-4 py-3 text-sm font-semibold transition {{ $isActive ? 'text-brand-600' : 'text-ink-500 hover:text-ink-900 hover:bg-ink-50' }}"
                style="border-bottom: 3px solid {{ $isActive ? '#0284c7' : 'transparent' }}; margin-bottom: -1px;"
                @if ($isActive) aria-current="page" @endif
                data-testid="multi-vendor-shop-tab-{{ $key }}">{{ $label }}
                @if (!empty($tabCounts[$key]))
                    <span class="rounded-full px-2 text-[10px] font-bold {{ $isActive ? 'bg-brand-600 text-white' : 'bg-ink-50 text-ink-500' }}"
                        style="padding-top: 2px; padding-bottom: 2px;">{{ number_format($tabCounts[$key]) }}</span>
                @endif
            </a>
        @endforeach
    </nav>
</section>
