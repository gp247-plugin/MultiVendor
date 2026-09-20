@extends($templatePathAdminVendor.'layout')

@section('main')

@if (!empty($dataNotFound))

    <x-gp247::card :title="$title_description ?? ''">
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ gp247_language_render('display.data_not_found') }}</p>
    </x-gp247::card>

@else

@php
    $urlUpdateStore = gp247_route_admin('vendor_admin_store.update');
    $descriptions = $store->descriptions->keyBy('lang');
    $savedMsg = gp247_language_render('admin.msg_change_success');

    $mediaFields = [
        'logo' => gp247_language_render('store.logo'),
        'icon' => gp247_language_render('store.icon'),
        'og_image' => gp247_language_render('store.og_image'),
    ];

    $contactFields = [
        'phone' => ['icon' => 'fas fa-phone-alt', 'label' => gp247_language_render('store.phone'), 'type' => 'number'],
        'long_phone' => ['icon' => 'fas fa-phone-square', 'label' => gp247_language_render('store.long_phone'), 'type' => 'text'],
        'time_active' => ['icon' => 'far fa-calendar-alt', 'label' => gp247_language_render('store.time_active'), 'type' => 'text'],
        'address' => ['icon' => 'fas fa-map-marked', 'label' => gp247_language_render('store.address'), 'type' => 'text'],
        'office' => ['icon' => 'fas fa-location-arrow', 'label' => gp247_language_render('store.office'), 'type' => 'text'],
        'warehouse' => ['icon' => 'fas fa-warehouse', 'label' => gp247_language_render('store.warehouse'), 'type' => 'text'],
        'email' => ['icon' => 'fas fa-envelope', 'label' => gp247_language_render('store.email'), 'type' => 'text'],
    ];

    $descriptionFields = [
        'title' => gp247_language_render('store.title'),
        'keyword' => gp247_language_render('store.keyword'),
        'description' => gp247_language_render('store.description'),
    ];

    $rowClass = 'px-4 py-3 text-gray-700 dark:text-gray-200';
@endphp

<x-gp247::card>
    <x-slot:header>
        <div>
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ $title ?? '' }}</h3>
        </div>
    </x-slot:header>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">

        {{-- Left: media + scalar store fields --}}
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">

                    @foreach ($mediaFields as $field => $label)
                    <tr>
                        <td class="{{ $rowClass }} w-40">{{ $label }}</td>
                        <td class="px-4 py-3">
                            <div x-data x-on:change="mvp.postAndNotify(
                                    @js($urlUpdateStore),
                                    { storeId: @js($store->id), name: @js($field), value: $event.target.value },
                                    @js($savedMsg)
                                )">
                                @include($templatePathAdminVendor.'component.media_input', [
                                    'name' => $field,
                                    'type' => 'vendor_logo',
                                    'value' => $store->{$field},
                                ])
                            </div>
                        </td>
                    </tr>
                    @endforeach

                    @foreach ($contactFields as $field => $meta)
                    <tr>
                        <td class="{{ $rowClass }}"><i class="{{ $meta['icon'] }} text-gray-400"></i> {{ $meta['label'] }}</td>
                        <td class="px-4 py-3">
                            @include($templatePathAdminVendor.'component.inline_field', [
                                'url' => $urlUpdateStore,
                                'storeId' => $store->id,
                                'name' => $field,
                                'type' => $meta['type'],
                                'value' => $store->{$field},
                            ])
                        </td>
                    </tr>
                    @endforeach

                    <tr>
                        <td class="{{ $rowClass }}"><i class="fas fa-object-ungroup text-gray-400"></i> {{ gp247_language_render('admin.store.template') }}</td>
                        <td class="px-4 py-3">
                            @include($templatePathAdminVendor.'component.inline_field', [
                                'url' => $urlUpdateStore,
                                'storeId' => $store->id,
                                'name' => 'template',
                                'type' => 'select',
                                'value' => $store->template,
                                'options' => $templates,
                            ])
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        {{-- Right: per-language descriptions --}}
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @foreach ($descriptionFields as $field => $label)
                    <tr>
                        <td class="{{ $rowClass }} w-40 align-top">{{ $label }}</td>
                        <td class="space-y-3 px-4 py-3">
                            @foreach ($languages->toArray() as $codeLang => $lang)
                            <div>
                                <div class="mb-1 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                    <img src="{{ gp247_file($languages[$codeLang]->icon) }}" class="h-4 w-5 object-cover" alt="{{ $languages[$codeLang]->name }}">
                                    <span>{{ $languages[$codeLang]->name }}</span>
                                </div>
                                @include($templatePathAdminVendor.'component.inline_field', [
                                    'url' => $urlUpdateStore,
                                    'storeId' => $store->id,
                                    'name' => $field.'__'.$codeLang,
                                    'type' => 'text',
                                    'value' => $descriptions[$codeLang][$field] ?? '',
                                ])
                            </div>
                            @endforeach
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
</x-gp247::card>

@endif

@endsection
