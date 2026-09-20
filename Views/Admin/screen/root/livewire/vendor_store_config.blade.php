{{--
    Per-vendor-store configuration (v2 Livewire port of the legacy store_config
    view's config_info tab): a single "store information" tab — media + scalar
    store fields, code and template on the left, per-language descriptions on the
    right. Every field persists live through the Livewire component (updatedStore
    / updatedDesc). Single-domain marketplace: no domain, no language/currency,
    no mail/captcha/display tabs.

    @aidlc-unit multi-vendor-pro
    @aidlc-story US-multi-vendor-pro-root-admin-livewire
    @aidlc-adr multi-vendor_admin-livewire-migration

    Variables: $pathPlugin, $languages, $mediaFields, $fields, $templateOptions.
--}}
@php
    $meta = [
        'logo' => ['label' => 'store.logo', 'icon' => 'far fa-image'],
        'icon' => ['label' => 'store.icon', 'icon' => 'far fa-image'],
        'og_image' => ['label' => 'store.og_image', 'icon' => 'far fa-image'],
        'phone' => ['label' => 'store.phone', 'icon' => 'fas fa-phone-alt'],
        'long_phone' => ['label' => 'store.long_phone', 'icon' => 'fas fa-phone-square'],
        'time_active' => ['label' => 'store.time_active', 'icon' => 'far fa-calendar-alt'],
        'address' => ['label' => 'store.address', 'icon' => 'fas fa-map-marked'],
        'office' => ['label' => 'store.office', 'icon' => 'fas fa-location-arrow'],
        'warehouse' => ['label' => 'store.warehouse', 'icon' => 'fas fa-warehouse'],
        'email' => ['label' => 'store.email', 'icon' => 'fas fa-envelope'],
        'code' => ['label' => 'admin.store.code', 'icon' => 'fas fa-code'],
        'template' => ['label' => 'admin.store.template', 'icon' => 'fas fa-object-ungroup'],
    ];

    $rows = [];
    foreach ($mediaFields as $f) { $rows[] = ['field' => $f, 'type' => 'media']; }
    foreach ($fields as $f) { $rows[] = ['field' => $f, 'type' => $f === 'time_active' ? 'textarea' : 'text']; }
    $rows[] = ['field' => 'code', 'type' => 'text', 'testid' => 'multi-vendor-pro-store-code'];
    if (!empty($templateOptions)) { $rows[] = ['field' => 'template', 'type' => 'select', 'options' => $templateOptions, 'testid' => 'multi-vendor-pro-store-template']; }

    $labelCell = 'w-2/5 border-r border-gray-200 px-5 py-3.5 align-middle text-sm font-medium text-gray-600 dark:border-gray-700 dark:text-gray-300';
    $valueCell = 'px-5 py-3 align-middle';
    $input = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
@endphp

<div class="space-y-5">
    <div class="flex justify-end">
        <x-gp247::button href="{{ gp247_route_admin('admin_MultiVendor.index') }}" variant="secondary" size="sm">
            <i class="fa fa-list"></i> {{ gp247_language_render('admin.back_list') }}
        </x-gp247::button>
    </div>

    <x-gp247::tabs :tabs="['info' => gp247_language_render('admin.store.config_info')]">
        <div x-show="tab === 'info'" x-cloak class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            {{-- Left: store fields --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 shadow-sm dark:border-gray-700">
                <table class="min-w-full">
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($rows as $i => $row)
                            @php $field = $row['field']; @endphp
                            <tr class="{{ $i % 2 ? 'bg-gray-50/60 dark:bg-gray-800/40' : 'bg-white dark:bg-gray-800' }}">
                                <td class="{{ $labelCell }}">
                                    @if (!empty($meta[$field]['icon']))<i class="{{ $meta[$field]['icon'] }} mr-1.5 w-4 text-center text-gray-400"></i>@endif
                                    {{ gp247_language_render($meta[$field]['label']) }}
                                </td>
                                <td class="{{ $valueCell }}">
                                    @switch($row['type'])
                                        @case('media')
                                            <x-gp247::media-input :name="$field" type="logo" :working-store="$storeId ?? ''" wire:model.live="store.{{ $field }}" :value="$store[$field] ?? null" />
                                            @break
                                        @case('textarea')
                                            <textarea wire:model.live.blur="store.{{ $field }}" rows="2" class="{{ $input }}"></textarea>
                                            @break
                                        @case('select')
                                            <x-gp247::searchable-select
                                                model="store.{{ $field }}"
                                                :options="collect($row['options'])->map(fn ($label, $value) => ['id' => (string) $value, 'label' => (string) $value])->values()->all()"
                                                :clearable="false"
                                                :data-testid="$row['testid'] ?? null"
                                            />
                                            @break
                                        @default
                                            <input type="text" wire:model.live.blur="store.{{ $field }}" class="{{ $input }}"
                                                @if (!empty($row['testid'])) data-testid="{{ $row['testid'] }}" @endif>
                                    @endswitch
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- S3-4: vendor plan (Pro) — assigning opens a new period and books the fee --}}
                @if (\App\GP247\Plugins\MultiVendor\Tier\Tier::allows(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_VENDOR_PLANS))
                <div class="px-5 py-4" data-testid="multi-vendor-plan-block">
                    <label for="store-plan" class="block text-sm font-medium text-gray-600 dark:text-gray-300">
                        <i class="fas fa-layer-group mr-1.5 w-4 text-center text-gray-400"></i>{{ gp247_language_render('multi_vendor.plan.plan') }}
                    </label>
                    <select id="store-plan" wire:model.live="planId" class="{{ $input }} mt-1.5 max-w-xs" data-testid="multi-vendor-plan-select">
                        <option value="">{{ gp247_language_render('multi_vendor.plan.default_option', ['name' => $planEffective && empty($planCurrent) ? $planEffective->name : '—']) }}</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }}@if ((float) $plan->fee_amount > 0) — {{ gp247_currency_format($plan->fee_amount) }} {{ $plan->fee_currency }} / {{ gp247_language_render('multi_vendor.plan.period_'.$plan->fee_period) }}@endif</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        @if (!empty($planCurrent))
                            {{ gp247_language_render('multi_vendor.plan.current_period', ['start' => $planCurrent->period_start?->format('Y-m-d'), 'end' => $planCurrent->period_end ? $planCurrent->period_end->format('Y-m-d') : '∞']) }}
                        @else
                            {{ gp247_language_render('multi_vendor.plan.assign_help') }}
                        @endif
                    </p>
                </div>
                @endif

                {{-- S1-1: per-store commission override (admin_config row, not a store column) --}}
                @if ($canOverrideCommission)
                <div class="border-t border-gray-200 bg-gray-50/60 px-5 py-4 dark:border-gray-700 dark:bg-gray-800/40">
                    <label for="store-commission" class="block text-sm font-medium text-gray-600 dark:text-gray-300">
                        <i class="fas fa-percent mr-1.5 w-4 text-center text-gray-400"></i>{{ gp247_language_render('multi_vendor.commission_override') }}
                    </label>
                    <input type="number" id="store-commission" min="0" max="100" step="1"
                        wire:model.live.blur="commission" class="{{ $input }} mt-1.5 max-w-xs"
                        placeholder="{{ $marketplaceRate }}"
                        data-testid="multi-vendor-pro-store-commission">
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        {{ gp247_language_render('multi_vendor.commission_override_help', ['rate' => $marketplaceRate]) }}
                    </p>
                </div>
                @else
                <div class="border-t border-gray-200 px-5 py-3 dark:border-gray-700" data-testid="multi-vendor-pro-store-commission-pro-hint">
                    @include('Plugins/MultiVendor::Admin.partials.pro_upsell', [
                        'audience'     => \App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::ROOT,
                        'variant'      => 'block',
                        'flag'         => \App\GP247\Plugins\MultiVendor\Tier\Tier::F_PER_VENDOR_COMMISSION,
                        'featureTitle' => gp247_language_render('multi_vendor.commission_override'),
                        'featureDesc'  => gp247_language_render(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_PER_VENDOR_COMMISSION)),
                        'gatewayUrl'   => \App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::gatewayUrl(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::ROOT, 'report'),
                    ])
                </div>
                @endif
            </div>

            {{-- Right: multilingual descriptions, one tab per language --}}
            @php $langTabs = []; foreach ($languages as $code => $lang) { $langTabs[$code] = $lang->name; } @endphp
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <x-gp247::tabs :tabs="$langTabs">
                    @foreach ($languages as $code => $lang)
                        <div x-show="tab === @js((string) $code)" x-cloak class="space-y-4">
                            @php $descLabels = ['name' => 'store.title', 'keyword' => 'store.keyword', 'description' => 'store.description']; @endphp
                            @foreach (['name' => 'text', 'keyword' => 'text', 'description' => 'textarea'] as $df => $control)
                                <div class="space-y-1">
                                    <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-200">
                                        @if ($lang->icon)<img src="{{ gp247_file($lang->icon) }}" alt="{{ $lang->name }}" class="h-4 w-auto rounded-sm">@endif
                                        {{ gp247_language_render($descLabels[$df]) }}
                                    </label>
                                    @if ($control === 'textarea')
                                        <textarea wire:model.live.blur="desc.{{ $code }}.{{ $df }}" rows="4" class="{{ $input }}"></textarea>
                                    @else
                                        <input type="text" wire:model.live.blur="desc.{{ $code }}.{{ $df }}" class="{{ $input }}">
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </x-gp247::tabs>
            </div>
        </div>
    </x-gp247::tabs>
</div>
