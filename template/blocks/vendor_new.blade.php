{{--
    "Top new vendors" storefront block.

    v1 rendered this as an owl-carousel inside a Bootstrap grid — both belong to
    the old default template and neither exists on a v2 (Tailwind) storefront.
    It is a plain responsive grid now, so the block works on any template; a
    template that wants a carousel can override this view.
--}}
<section class="py-12">
    <div class="mx-auto w-full max-w-7xl px-4">
        <h3 class="mb-6 text-xl font-normal">{{ gp247_language_render('Plugins/MultiVendor::lang.top_new_vendor') }}</h3>

        @if (function_exists('gp247_vendor_top_new') && count(gp247_vendor_top_new()))
        <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
            @foreach (gp247_vendor_top_new() as $vendor)
            <div class="flex flex-col items-center gap-2 text-center text-sm">
                <a href="{{ gp247_vendor_get_url($vendor->code) }}">
                    <img src="{{ gp247_file($vendor->logo) }}" alt="{{ $vendor->code }}" width="87" height="87"
                        class="h-[87px] w-[87px] rounded-full object-cover">
                </a>

                <span>Shop:
                    <a href="{{ gp247_vendor_get_url($vendor->code) }}"
                        class="font-medium text-blue-600 hover:underline">{{ $vendor->code }}</a>
                </span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
