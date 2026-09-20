{{--
    Reusable "this is a Pro feature" block.

    Two variants and two voices, because the same fact reaches two different
    people: the marketplace owner is the buyer (gets the upgrade button), the
    shop owner is not (gets "ask your marketplace", never a price). Customers on
    the storefront never see this block at all.

    @aidlc-unit multi-vendor-pro
    @aidlc-story US-multi-vendor-pro-upgrade-funnel

    Params:
      $audience     - 'root' (marketplace owner) | 'vendor' (shop owner)
      $variant      - 'screen' (full gateway page) | 'block' (inline, inside a Free screen)
      $featureTitle - localized feature name (may be '')
      $featureDesc  - localized one-liner (may be '')
      $flag         - Tier flag, for the inline block's test hook (optional)
      $features     - catalogue rows [flag,title,desc,available] for the 'screen' variant (optional)
--}}
@php
    $variant = $variant ?? 'screen';
    $audience = $audience ?? \App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::ROOT;
    $isRoot = $audience === \App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::ROOT;
    $features = $features ?? [];
    $flag = $flag ?? '';
@endphp

@if ($variant === 'block')
    <div class="rounded-lg border border-amber-200 bg-amber-50/70 px-4 py-3 text-sm dark:border-amber-900/50 dark:bg-amber-900/20"
        data-testid="multi-vendor-pro-upsell-block{{ $flag !== '' ? '-'.$flag : '' }}">
        <p class="font-medium text-amber-800 dark:text-amber-200">
            <i class="fas fa-lock mr-1.5"></i>{{ $featureTitle }}
        </p>
        @if (!empty($featureDesc))
            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $featureDesc }}</p>
        @endif
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
            {{ gp247_language_render($isRoot ? 'multi_vendor.pro.blurb_root' : 'multi_vendor.pro.blurb_vendor') }}
        </p>
        @if (isset($gatewayUrl) && $gatewayUrl !== '')
            <a href="{{ $gatewayUrl }}" class="mt-1.5 inline-flex items-center gap-1.5 text-xs font-semibold text-amber-700 underline dark:text-amber-300">
                <i class="fas fa-info-circle"></i>{{ gp247_language_render('multi_vendor.pro.learn_more') }}
            </a>
        @endif
    </div>
@else
    {{--
        Screen variant. Every class below exists in the pre-built core admin
        bundle (rule gp247 §3a: no JIT at runtime), and icons are Font Awesome 5.
    --}}
    <div class="mx-auto max-w-4xl" data-testid="multi-vendor-pro-upsell">
        {{-- Hero: what this screen is, and the one action for the reader --}}
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm dark:border-amber-900 dark:bg-gray-800 sm:p-8">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-lg">
                    <i class="fas fa-crown text-2xl"></i>
                </div>

                <div class="min-w-0 flex-1">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                        <i class="fas fa-lock text-[10px]"></i>
                        {{ gp247_language_render('multi_vendor.pro.heading') }}
                    </span>

                    @if (!empty($featureTitle))
                        <h2 class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $featureTitle }}</h2>
                    @endif

                    @if (!empty($featureDesc))
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $featureDesc }}</p>
                    @endif

                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                        {{ gp247_language_render($isRoot ? 'multi_vendor.pro.blurb_root' : 'multi_vendor.pro.blurb_vendor') }}
                    </p>
                </div>

                <div class="shrink-0">
                    @if ($isRoot)
                        <a href="{{ gp247_language_render('multi_vendor.pro.buy_url') }}" target="_blank" rel="noopener"
                            class="inline-flex w-full items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-400"
                            data-testid="multi-vendor-pro-upsell-cta">
                            <i class="fas fa-crown"></i>
                            {{ gp247_language_render('multi_vendor.pro.cta') }}
                            <i class="fas fa-external-link-alt text-xs"></i>
                        </a>
                    @else
                        <p class="inline-flex items-center gap-2 rounded-lg border border-amber-200 bg-white px-4 py-2.5 text-sm font-medium text-amber-800 dark:border-amber-800 dark:bg-gray-900 dark:text-amber-200"
                            data-testid="multi-vendor-pro-upsell-vendor-note">
                            <i class="fas fa-comments"></i>
                            {{ gp247_language_render('multi_vendor.pro.vendor_note') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Catalogue: one card per Pro feature, two columns from sm up --}}
        @if (!empty($features))
            <div class="mt-5 rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center gap-2 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <i class="fas fa-crown text-amber-500"></i>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                        {{ gp247_language_render('multi_vendor.pro.list_title') }}
                    </h3>
                </div>

                <ul class="grid gap-3 p-5 sm:grid-cols-2">
                    @foreach ($features as $row)
                        <li class="flex items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 transition-colors hover:border-amber-300 hover:bg-amber-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-700"
                            data-testid="multi-vendor-pro-feature-{{ $row['flag'] }}">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $row['available'] ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900 dark:text-emerald-300' : 'bg-white text-amber-600 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-amber-300 dark:ring-gray-600' }}">
                                <i class="{{ $row['icon'] ?? 'fas fa-star' }}"></i>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-start justify-between gap-2">
                                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $row['title'] }}</span>
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $row['available'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/20 dark:text-amber-200' }}">
                                        @if ($row['available'])<i class="fas fa-check"></i> @endif{{ gp247_language_render($row['available'] ? 'multi_vendor.pro.included' : 'multi_vendor.pro.locked') }}
                                    </span>
                                </span>
                                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ $row['desc'] }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>

                @if ($isRoot)
                    <div class="flex flex-col items-center justify-between gap-3 border-t border-gray-200 px-5 py-4 dark:border-gray-700 sm:flex-row">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ gp247_language_render('multi_vendor.pro.blurb_root') }}
                        </p>
                        <a href="{{ gp247_language_render('multi_vendor.pro.buy_url') }}" target="_blank" rel="noopener"
                            class="inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-lg border border-amber-300 bg-white px-4 py-2 text-sm font-semibold text-amber-800 transition-colors hover:bg-amber-100 dark:border-amber-800 dark:bg-gray-900 dark:text-amber-200 dark:hover:bg-gray-700"
                            data-testid="multi-vendor-pro-upsell-cta-footer">
                            <i class="fas fa-crown"></i>
                            {{ gp247_language_render('multi_vendor.pro.cta') }}
                        </a>
                    </div>
                @endif
            </div>
        @endif
    </div>
@endif
