{{--
    "Top new vendors" storefront block.

    v1 rendered this as an owl-carousel inside a Bootstrap grid — both belong to
    the old default template and neither exists on a v2 (Tailwind) storefront.
    It is a plain responsive grid now, so the block works on any template; a
    template that wants a carousel can override this view.

    WHY only template component classes (container-x, section-title, nav-link,
    product-card) and utilities already used by the template's own blocks: the
    storefront CSS is a prebuilt Tailwind bundle whose scan does not include
    plugin folders, so a class that appears only here would have no CSS rule and
    silently fall back to unstyled markup (that is how the v1 port ended up with a
    heading glued to the page edge). Header row and card follow the home strips
    (blocks/shop_product_promotion) and the vendor directory (vendor_index).

    WHY object-contain on a padded tile: vendor logos are often wide wordmarks;
    a round object-cover crop cut their text off.
--}}
@php
    $newVendors = function_exists('gp247_vendor_top_new') ? gp247_vendor_top_new() : collect();
@endphp
@if (count($newVendors))
<section class="container-x py-6" data-testid="multi-vendor-block-new">
    <div class="flex items-center justify-between mb-4">
        <h2 class="section-title">{{ gp247_language_render('Plugins/MultiVendor::lang.top_new_vendor') }}</h2>
        <a href="{{ gp247_route_front('MultiVendor.index') }}" class="nav-link">{{ gp247_language_quickly('front.view_all', 'View all') }}</a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach ($newVendors as $vendor)
            @php $vendorName = $vendor->getTitle() ?: $vendor->code; @endphp
            {{-- gp247_vendor_get_url() looks the store up by id, not by code. --}}
            <a href="{{ gp247_vendor_get_url((string) $vendor->id) }}" class="product-card group items-center text-center gap-3 p-4" data-testid="multi-vendor-block-new-card">
                <span class="w-20 h-20 rounded-2xl bg-ink-50 flex items-center justify-center overflow-hidden p-2 group-hover:bg-brand-50 transition">
                    @if (!empty($vendor->logo))
                        <img src="{{ gp247_file($vendor->logo) }}" alt="{{ $vendorName }}" class="w-full h-full object-contain" loading="lazy">
                    @else
                        <span class="text-2xl font-bold text-ink-400">{{ mb_substr($vendorName, 0, 1) }}</span>
                    @endif
                </span>
                <span class="w-full">
                    <span class="block text-sm font-medium text-ink-800 clamp-1 group-hover:text-brand-600">{{ $vendorName }}</span>
                    <span class="block text-xs text-ink-400 mt-1">{{ gp247_language_render('multi_vendor.shop.joined') }} {{ $vendor->created_at ? $vendor->created_at->format('m/Y') : '—' }}</span>
                </span>
            </a>
        @endforeach
    </div>
</section>
@endif
