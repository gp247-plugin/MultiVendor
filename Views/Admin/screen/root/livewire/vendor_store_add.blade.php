{{--
    "Add new vendor store" form (v2 Livewire port of the legacy store_add view):
    two-column layout — a left definition-table of store fields (media, contact
    details, code, template select, open/close status) and a right card of
    per-language SEO descriptions grouped under one tab per language. Submits via
    Livewire save().

    Single-domain marketplace: no domain field, no language/currency selects
    (both follow the marketplace), no maintenance editor. A `status` toggle is
    present so a booth can be created open or closed.

    @aidlc-unit multi-vendor-pro
    @aidlc-story US-multi-vendor-pro-root-admin-livewire
    @aidlc-adr multi-vendor_admin-livewire-migration

    Variables: $pathPlugin, $languages, $templateOptions.
--}}
@php
    $meta = [
        'logo' => ['label' => 'store.logo', 'icon' => 'far fa-image', 'type' => 'media'],
        'phone' => ['label' => 'store.phone', 'icon' => 'fas fa-phone-alt', 'type' => 'text'],
        'long_phone' => ['label' => 'store.long_phone', 'icon' => 'fas fa-phone-square', 'type' => 'text'],
        'email' => ['label' => 'store.email', 'icon' => 'fas fa-envelope', 'type' => 'text'],
        'time_active' => ['label' => 'store.time_active', 'icon' => 'far fa-calendar-alt', 'type' => 'text'],
        'address' => ['label' => 'store.address', 'icon' => 'fas fa-map-marked', 'type' => 'text'],
        'office' => ['label' => 'store.office', 'icon' => 'fas fa-location-arrow', 'type' => 'text'],
        'warehouse' => ['label' => 'store.warehouse', 'icon' => 'fas fa-warehouse', 'type' => 'text'],
        'code' => ['label' => 'admin.store.code', 'icon' => 'fas fa-code', 'type' => 'text', 'required' => true, 'maxlength' => 20, 'testid' => 'multi-vendor-pro-store-code'],
        'template' => ['label' => 'admin.store.template', 'icon' => 'fas fa-object-ungroup', 'type' => 'select', 'required' => true, 'options' => $templateOptions, 'testid' => 'multi-vendor-pro-store-template'],
    ];

    $labelCell = 'w-2/5 border-r border-gray-200 px-5 py-3.5 align-middle text-sm font-medium text-gray-600 dark:border-gray-700 dark:text-gray-300';
    $valueCell = 'px-5 py-3 align-middle';
    $input = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
    $label = 'text-sm font-medium text-gray-700 dark:text-gray-200';
    $error = 'mt-1 text-xs text-red-500';
@endphp

<div class="space-y-5">
    <div class="flex justify-end">
        <x-gp247::button href="{{ gp247_route_admin('admin_MultiVendor.index') }}" variant="secondary" size="sm">
            <i class="fa fa-list"></i> {{ gp247_language_render('admin.back_list') }}
        </x-gp247::button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            {{-- Left: store fields (definition table, mirrors store_info) --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 shadow-sm dark:border-gray-700">
                <table class="min-w-full">
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($meta as $field => $def)
                            <tr class="{{ $loop->index % 2 ? 'bg-gray-50/60 dark:bg-gray-800/40' : 'bg-white dark:bg-gray-800' }}">
                                <td class="{{ $labelCell }}">
                                    <label for="store__{{ $field }}">
                                        <i class="{{ $def['icon'] }} mr-1.5 w-4 text-center text-gray-400"></i>{{ gp247_language_render($def['label']) }}
                                        @if (!empty($def['required']))<span class="text-red-500">*</span>@endif
                                    </label>
                                </td>
                                <td class="{{ $valueCell }}">
                                    @switch($def['type'])
                                        @case('media')
                                            <x-gp247::media-input :name="$field" type="logo" wire:model="store.{{ $field }}" :value="$store[$field] ?? null" />
                                            @break
                                        @case('select')
                                            <select id="store__{{ $field }}" wire:model="store.{{ $field }}" class="{{ $input }}"
                                                @if (!empty($def['testid'])) data-testid="{{ $def['testid'] }}" @endif>
                                                <option value=""></option>
                                                @foreach ($def['options'] as $value => $name)
                                                    {{-- WHY: template options key by folder name — show the
                                                         stable folder, not the display label, like legacy. --}}
                                                    <option value="{{ $value }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                            @break
                                        @default
                                            <input type="text" id="store__{{ $field }}" wire:model="store.{{ $field }}"
                                                class="{{ $input }}"
                                                @if (!empty($def['maxlength'])) maxlength="{{ $def['maxlength'] }}" @endif
                                                @if (!empty($def['testid'])) data-testid="{{ $def['testid'] }}" @endif>
                                    @endswitch
                                    @error('store.'.$field)<p class="{{ $error }}">{{ $message }}</p>@enderror
                                </td>
                            </tr>
                        @endforeach

                        {{-- Open/close (`status`) toggle: create a booth open or closed. --}}
                        <tr class="{{ count($meta) % 2 ? 'bg-gray-50/60 dark:bg-gray-800/40' : 'bg-white dark:bg-gray-800' }}">
                            <td class="{{ $labelCell }}">
                                <i class="fas fa-toggle-on mr-1.5 w-4 text-center text-gray-400"></i>{{ gp247_language_render('admin.store.status') }}
                            </td>
                            <td class="{{ $valueCell }}">
                                <label class="inline-flex cursor-pointer items-center">
                                    <input type="checkbox" wire:model="store.status" class="peer sr-only"
                                        data-testid="multi-vendor-pro-store-status">
                                    <span class="relative h-6 w-11 rounded-full bg-gray-300 transition-colors after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-all peer-checked:bg-blue-600 peer-checked:after:translate-x-5 dark:bg-gray-600"></span>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Right: multilingual descriptions, grouped under one tab per language --}}
            @php $langTabs = []; foreach ($languages as $code => $lang) { $langTabs[$code] = $lang->name; } @endphp
            {{-- WHY: panes are hidden client-side, so a failing language other than the
                 open one would be invisible — mark its tab via the tabs `errors` prop. --}}
            @php($tabsWithErrors = array_values(array_intersect(
                array_map('strval', array_keys($langTabs)),
                array_unique(array_map(static fn ($k) => explode('.', $k)[1] ?? '', preg_grep('/^descriptions\./', $errors->keys())))
            )))
            <div class="self-start rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <x-gp247::tabs :tabs="$langTabs" :errors="$tabsWithErrors">
                    @foreach ($languages as $code => $lang)
                        <div x-show="tab === @js((string) $code)" x-cloak class="space-y-4">
                            <div class="space-y-1">
                                <label for="{{ $code }}__title" class="{{ $label }} flex items-center gap-1.5">
                                    @if ($lang->icon)<img src="{{ gp247_file($lang->icon) }}" alt="{{ $lang->name }}" class="h-4 w-auto rounded-sm">@endif
                                    {{ gp247_language_render('store.title') }} <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="{{ $code }}__title" wire:model="descriptions.{{ $code }}.title" class="{{ $input }}" maxlength="200">
                                @error('descriptions.'.$code.'.title')<p class="{{ $error }}">{{ $message }}</p>@enderror
                            </div>
                            <div class="space-y-1">
                                <label for="{{ $code }}__keyword" class="{{ $label }} flex items-center gap-1.5">
                                    @if ($lang->icon)<img src="{{ gp247_file($lang->icon) }}" alt="{{ $lang->name }}" class="h-4 w-auto rounded-sm">@endif
                                    {{ gp247_language_render('store.keyword') }}
                                </label>
                                <input type="text" id="{{ $code }}__keyword" wire:model="descriptions.{{ $code }}.keyword" class="{{ $input }}" maxlength="200">
                                @error('descriptions.'.$code.'.keyword')<p class="{{ $error }}">{{ $message }}</p>@enderror
                            </div>
                            <div class="space-y-1">
                                <label for="{{ $code }}__description" class="{{ $label }} flex items-center gap-1.5">
                                    @if ($lang->icon)<img src="{{ gp247_file($lang->icon) }}" alt="{{ $lang->name }}" class="h-4 w-auto rounded-sm">@endif
                                    {{ gp247_language_render('store.description') }}
                                </label>
                                <textarea id="{{ $code }}__description" wire:model="descriptions.{{ $code }}.description" rows="3" class="{{ $input }}" maxlength="300"></textarea>
                                @error('descriptions.'.$code.'.description')<p class="{{ $error }}">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    @endforeach
                </x-gp247::tabs>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <x-gp247::button type="submit" variant="primary" wire:loading.attr="disabled"
                data-testid="multi-vendor-pro-store-submit">
                {{ gp247_language_render('action.submit') }}
            </x-gp247::button>
        </div>
    </form>
</div>
