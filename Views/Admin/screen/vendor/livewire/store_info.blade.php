{{--
    Vendor store-info screen — copies the core WebsiteInfo (store_info) layout so the
    vendor screen looks identical to the core admin store-settings screen: a left
    definition-table of the vendor's own store fields (media + contact details, live
    inline edit) and a right table of multilingual descriptions (name/keyword/
    description per active language). Bordered, striped two-column layout.

    SECURITY — this is the marketplace vendor variant: currency / language / template
    / domain / code are platform-owned and are NOT rendered here (the rows are dropped
    outright, not merely fed empty options), and the store Active / maintenance toggle
    is removed (decision Q3-A: a vendor has no maintenance control). The write side is
    enforced independently in VendorStoreInfoForm::updatedStore().

    @aidlc-unit multi-vendor-pro
    @aidlc-story US-multi-vendor-pro-vendor-admin-livewire
    @aidlc-adr multi-vendor_admin-livewire-migration

    Variables:
      - $languages (array<code, AdminLanguage>)
      - $mediaFields (string[]), $fields (string[])
--}}
@php
    $meta = [
        'logo' => ['label' => 'store.logo', 'icon' => 'far fa-image'],
        'icon' => ['label' => 'store.icon', 'icon' => 'far fa-image'],
        'og_image' => ['label' => 'store.og_image', 'icon' => 'far fa-image'],
        'phone' => ['label' => 'store.phone', 'icon' => 'fas fa-phone-alt'],
        'long_phone' => ['label' => 'store.long_phone', 'icon' => 'fas fa-phone-square'],
        'email' => ['label' => 'store.email', 'icon' => 'fas fa-envelope'],
        'time_active' => ['label' => 'store.time_active', 'icon' => 'far fa-calendar-alt'],
        'address' => ['label' => 'store.address', 'icon' => 'fas fa-map-marked'],
        'office' => ['label' => 'store.office', 'icon' => 'fas fa-location-arrow'],
        'warehouse' => ['label' => 'store.warehouse', 'icon' => 'fas fa-warehouse'],
    ];

    // Build the ordered left-column row list — media + contact fields ONLY. The
    // language / domain / currency / template rows from core are dropped entirely:
    // those fields are platform-owned and must never reach the vendor screen.
    $rows = [];
    foreach ($mediaFields as $f) { $rows[] = ['field' => $f, 'type' => 'media']; }
    foreach ($fields as $f) { $rows[] = ['field' => $f, 'type' => $f === 'time_active' ? 'textarea' : 'text']; }

    $labelCell = 'w-2/5 border-r border-gray-200 px-5 py-3.5 align-middle text-sm font-medium text-gray-600 dark:border-gray-700 dark:text-gray-300';
    $valueCell = 'px-5 py-3 align-middle';
    $input = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
@endphp

{{-- WHY the single wrapping <div>: a Livewire full-page component must have ONE
     stable root element — never open with @if/@else (two possible roots would let
     Livewire bind its wire:id to the outer layout and break inline persistence). --}}
<div class="space-y-6" data-testid="multi-vendor-pro-store-info-screen">
    {{-- WHY lg: (not core's xl:): with the admin sidebar the content area is usually
         < 1280px, so xl:grid-cols-2 stayed one column; lg: gives the two-panel
         layout at ≥1024px, consistent with the category screen. --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Left: store fields (media + contact only) --}}
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
                                        <x-gp247::media-input :name="$field" type="logo" wire:model.live="store.{{ $field }}" :value="$store[$field] ?? null" data-testid="multi-vendor-pro-store-info-{{ $field }}" />
                                        @break
                                    @case('textarea')
                                        <textarea wire:model.live.blur="store.{{ $field }}" rows="2" class="{{ $input }}" data-testid="multi-vendor-pro-store-info-{{ $field }}"></textarea>
                                        @break
                                    @default
                                        <input type="text" wire:model.live.blur="store.{{ $field }}" class="{{ $input }}" data-testid="multi-vendor-pro-store-info-{{ $field }}">
                                @endswitch
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Right: multilingual descriptions, grouped under one tab per language.
             Maintenance copy is intentionally NOT folded in here (Q3-A). --}}
        @php $langTabs = []; foreach ($languages as $code => $lang) { $langTabs[$code] = $lang->name; } @endphp
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <x-gp247::tabs :tabs="$langTabs">
                @foreach ($languages as $code => $lang)
                    <div x-show="tab === @js((string) $code)" x-cloak class="space-y-4">
                        {{-- WHY: field key is `name` (matches the admin_store_description.name
                             column / $desc[lang]['name'] state) but the label i18n key stays
                             `store.title` — a pre-existing translation string, unchanged. --}}
                        @php $descLabels = ['name' => 'store.title', 'keyword' => 'store.keyword', 'description' => 'store.description']; @endphp
                        @foreach (['name' => 'text', 'keyword' => 'text', 'description' => 'textarea'] as $df => $control)
                            <div class="space-y-1">
                                <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-200">
                                    @if ($lang->icon)<img src="{{ gp247_file($lang->icon) }}" alt="{{ $lang->name }}" class="h-4 w-auto rounded-sm">@endif
                                    {{ gp247_language_render($descLabels[$df]) }}
                                </label>
                                @if ($control === 'textarea')
                                    <textarea wire:model.live.blur="desc.{{ $code }}.{{ $df }}" rows="4" class="{{ $input }}" data-testid="multi-vendor-pro-store-info-desc-{{ $df }}"></textarea>
                                @else
                                    <input type="text" wire:model.live.blur="desc.{{ $code }}.{{ $df }}" class="{{ $input }}" data-testid="multi-vendor-pro-store-info-desc-{{ $df }}">
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </x-gp247::tabs>
        </div>
    </div>
</div>
