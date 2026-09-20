@php
/*
* This template only use for MultiVendor
$layout_page = vendor_home
Shop page (S2-5): shared header (vendor_info, whose cover is the store banner) + tabs
  products (in-shop search / category chips / sort / grid / pagination),
  reviews (ProductRating seller panel — only when enabled for the store),
  info (full description, contact, hours).
Variables: $store, $stats, $storeId, $storeCode, $tab, $products (paginator, products tab),
           $banners, $categories, $searchKeyword, $categoryId, $filter_sort, $appPath.

Only classes the GP247Front bundle ships are used (rule gp247.md §3b); fixed
heights and clamping are inline styles, because the bundle has no utility for
them and a made-up class would silently do nothing.
*/
$t = fn (string $k, array $v = []) => gp247_language_render('multi_vendor.shop.'.$k, $v);
$shopUrl = gp247_route_front('MultiVendor.detail', ['code' => $storeCode]);
$hasFilter = $searchKeyword !== '' || $categoryId !== '';
@endphp

@extends($GP247TemplatePath.'.layout')

@section('block_main_content_center')

  @php
    $view = gp247_plugin_process_view($appPath, $GP247TemplatePath, 'vendor_info');
  @endphp
  @includeIf($view, ['store' => $store, 'stats' => $stats, 'storeCode' => $storeCode, 'tab' => $tab, 'storeId' => $storeId, 'banners' => $banners])

  @if ($tab === 'reviews')
    {{-- Reviews: the ProductRating plugin's seller panel (reviews of every product this store sells) --}}
    <div data-testid="multi-vendor-shop-reviews">
      @if (!empty($stats['ratingEnabled']))
        @livewire('gp247-productrating-front::store-rating-box', ['sellerStoreId' => $store->id])
      @else
        <div class="card p-6 text-center text-sm text-ink-400">{{ $t('no_rating') }}</div>
      @endif
    </div>

  @elseif ($tab === 'info')
    <div class="card p-6" data-testid="multi-vendor-shop-info">
      <h2 class="section-title mb-4">{{ $t('tab_info') }}</h2>
      <div class="text-sm text-ink-700 leading-relaxed mb-6">{!! $store->getDescription() ?: '<span class="text-ink-400">—</span>' !!}</div>
      <dl class="text-sm text-ink-700">
        @if (!empty($store->address))<div class="flex gap-4 py-3 border-t border-ink-100"><dt class="w-24 shrink-0 text-ink-400">{{ $t('address') }}</dt><dd>{{ $store->address }}</dd></div>@endif
        @if (!empty($store->phone))<div class="flex gap-4 py-3 border-t border-ink-100"><dt class="w-24 shrink-0 text-ink-400">{{ $t('phone') }}</dt><dd>{{ $store->phone }}</dd></div>@endif
        @if (!empty($store->email))<div class="flex gap-4 py-3 border-t border-ink-100"><dt class="w-24 shrink-0 text-ink-400">{{ $t('email') }}</dt><dd>{{ $store->email }}</dd></div>@endif
        @if (!empty($store->time_active))<div class="flex gap-4 py-3 border-t border-ink-100"><dt class="w-24 shrink-0 text-ink-400">{{ $t('hours') }}</dt><dd>{{ $store->time_active }}</dd></div>@endif
        <div class="flex gap-4 py-3 border-t border-ink-100"><dt class="w-24 shrink-0 text-ink-400">{{ $t('joined') }}</dt><dd>{{ !empty($stats['joined']) ? $stats['joined']->format('d/m/Y') : '—' }}</dd></div>
      </dl>
    </div>

  @else
    {{-- Products: search, categories and sort gathered into one toolbar so the
         grid starts at a predictable place instead of after three loose rows. --}}
    <div data-testid="multi-vendor-shop-products">
      <div class="card overflow-hidden mb-6">
        <form method="GET" action="{{ $shopUrl }}" class="flex flex-wrap items-center gap-2 p-4" data-testid="multi-vendor-shop-search">
          <input type="text" name="q" value="{{ $searchKeyword }}" placeholder="{{ $t('search_placeholder') }}" class="input flex-1 min-w-0"
              data-testid="multi-vendor-shop-search-input">
          @if ($categoryId !== '')<input type="hidden" name="cat" value="{{ $categoryId }}">@endif
          @if ($filter_sort !== '')<input type="hidden" name="filter_sort" value="{{ $filter_sort }}">@endif
          <button type="submit" class="btn-primary" data-testid="multi-vendor-shop-search-submit">{{ $t('search') }}</button>
          @if ($hasFilter)
            <a href="{{ $shopUrl }}" class="btn-outline">{{ $t('clear_filter') }}</a>
          @endif
        </form>

        @if ($categories->isNotEmpty())
          <div class="flex flex-wrap gap-2 px-4" style="padding-bottom: 1rem;" data-testid="multi-vendor-shop-categories">
            <a href="{{ $shopUrl }}{{ $searchKeyword !== '' ? '?q='.urlencode($searchKeyword) : '' }}"
                class="btn-sm rounded-full whitespace-nowrap transition {{ $categoryId === '' ? 'btn-primary' : 'btn-outline' }}">{{ $t('all_categories') }}</a>
            @foreach ($categories as $category)
              <a href="{{ $shopUrl }}?cat={{ $category->id }}{{ $searchKeyword !== '' ? '&q='.urlencode($searchKeyword) : '' }}"
                  class="btn-sm rounded-full whitespace-nowrap transition {{ (string) $categoryId === (string) $category->id ? 'btn-primary' : 'btn-outline' }}">{{ $category->title ?? $category->getTitle() }}</a>
            @endforeach
          </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-t border-ink-100 bg-ink-50">
          @include($GP247TemplatePath.'.common.pagination_result', ['items' => $products])
          @php $sortView = gp247_shop_process_view($GP247TemplatePath, 'common.shop_product_filter_sort'); @endphp
          @if (view()->exists($sortView))
            @include($sortView, ['filterSort' => $filter_sort])
          @endif
        </div>
      </div>

      @if ($products->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
          @foreach ($products as $product)
            {{-- Same product card the template's own lists use (Livewire, gp247/shop) --}}
            @livewire('gp247-shop-front::product-card', ['productId' => $product->id], key('mv-shop-card-'.$product->id))
          @endforeach
        </div>
      @else
        <div class="card text-center py-20" data-testid="multi-vendor-shop-empty">
          <svg class="w-16 h-16 text-ink-200 mx-auto mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.4a1.5 1.5 0 011.47 1.19L5.7 7.5m0 0l1.8 8.4a1.5 1.5 0 001.47 1.2h7.9a1.5 1.5 0 001.47-1.16l1.6-6.9A1.2 1.2 0 0018.77 7.5H5.7zM8.25 21a.75.75 0 100-1.5.75.75 0 000 1.5zm9 0a.75.75 0 100-1.5.75.75 0 000 1.5z"/>
          </svg>
          <p class="text-lg font-semibold text-ink-700">{!! gp247_language_render('front.no_item') !!}</p>
          @if ($hasFilter)
            <a href="{{ $shopUrl }}" class="btn-outline mt-4">{{ $t('clear_filter') }}</a>
          @endif
        </div>
      @endif

      @includeIf($GP247TemplatePath.'.common.pagination', ['items' => $products])
    </div>
  @endif

@endsection
