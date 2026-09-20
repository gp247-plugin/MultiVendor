@php
/*
* This template only use for MultiVendor
$layout_page = vendor_index
Marketplace directory (S2-5): every open vendor store as a card — logo, name,
sellable product count, seller rating (ProductRating, when enabled), member
since — with a name search and pagination.
Variables: $stores (paginator of AdminStore), $counts (store id => products),
           $ratings (store id => summary), $searchKeyword.
*/
$t = fn (string $k, array $v = []) => gp247_language_render('multi_vendor.shop.'.$k, $v);
@endphp

@extends($GP247TemplatePath.'.layout')

@section('block_main_content_center')
<div data-testid="multi-vendor-directory">
    <h1 class="section-title mb-4">{{ $t('directory_title') }}</h1>

    <form method="GET" action="{{ gp247_route_front('MultiVendor.index') }}" class="flex flex-wrap items-center gap-2 mb-6" data-testid="multi-vendor-directory-search">
        <input type="text" name="q" value="{{ $searchKeyword }}" placeholder="{{ $t('directory_search') }}" class="input flex-1 min-w-0"
            data-testid="multi-vendor-directory-search-input">
        <button type="submit" class="btn-primary btn-sm">{{ $t('search') }}</button>
        @if ($searchKeyword !== '')
            <a href="{{ gp247_route_front('MultiVendor.index') }}" class="text-sm text-ink-500 underline">{{ $t('clear_filter') }}</a>
        @endif
    </form>

    @if ($stores->count())
        @include($GP247TemplatePath.'.common.pagination_result', ['items' => $stores])
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($stores as $store)
                @php $sid = (string) $store->id; $rating = $ratings[$sid] ?? null; @endphp
                <article class="product-card group" data-testid="multi-vendor-directory-card">
                    <a href="{{ gp247_vendor_get_url($store->id) }}" class="block relative aspect-square overflow-hidden bg-ink-50">
                        @if (!empty($store->logo))
                            <img src="{{ gp247_file($store->logo) }}" alt="{{ $store->getTitle() ?: $store->code }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-2xl font-bold text-ink-400">{{ mb_substr($store->getTitle() ?: $store->code, 0, 1) }}</div>
                        @endif
                    </a>
                    <div class="p-3">
                        <a href="{{ gp247_vendor_get_url($store->id) }}" class="text-sm font-medium text-ink-800 clamp-2 hover:text-brand-600">{{ $store->getTitle() ?: $store->code }}
                            @if (!empty($verified) && in_array($sid, $verified, true))
                                <span class="inline-flex items-center rounded-full bg-brand-600 text-white text-[10px] font-bold px-1.5 py-0.5 align-middle" title="{{ gp247_language_render('multi_vendor.kyc.verified') }}" data-testid="multi-vendor-directory-verified">✓</span>
                            @endif
                        </a>
                        <div class="text-xs text-ink-500 mt-1">
                            <span class="font-semibold text-ink-900">{{ number_format((int) ($counts[$sid] ?? 0)) }}</span> {{ $t('products_count') }}
                            @if ($rating && $rating['total'] > 0)
                                · <span class="font-semibold text-brand-600">{{ $rating['average'] }}</span> ({{ $rating['total'] }})
                            @endif
                        </div>
                        <div class="text-xs text-ink-400">{{ $t('joined') }} {{ $store->created_at ? $store->created_at->format('m/Y') : '—' }}</div>
                    </div>
                </article>
            @endforeach
        </div>
        @includeIf($GP247TemplatePath.'.common.pagination', ['items' => $stores])
    @else
        <div class="text-center py-20" data-testid="multi-vendor-directory-empty">
            <p class="text-lg font-semibold text-ink-700">{{ $t('no_stores') }}</p>
        </div>
    @endif
</div>
@endsection
