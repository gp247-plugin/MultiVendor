{{--
    Vendor supplier panel (Pha 2 L1) — two-panel: add/edit form (left) + list
    (right), on the core ResourcePanel base (via VendorResourcePanel) + the shared
    HasCustomFields trait. Mirrors the core admin two-panel skeleton (P1/P3) but
    store-scoped to the signed-in vendor. Supplier is FLAT (no per-language
    descriptions), so there are no language tabs — the fields bind straight to
    form.*; the admin custom fields (type shop_supplier) bind to customFields.*.

    @aidlc-unit multi-vendor-pro
    @aidlc-story US-multi-vendor-pro-vendor-admin-livewire
    @aidlc-adr multi-vendor_admin-livewire-migration

    State: $rows (ShopSupplier paginator); $form, $customFields, $editingId,
    $keyword, $sortField, $sortDir (ResourcePanel + trait). Field defs via
    $this->customFields().
--}}
@php($inputCls = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100')
@php($labelCls = 'mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200')
@php($errCls = 'mt-1 text-xs text-red-500')

@php($customFieldDefs = $this->customFields())

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    {{-- Left: add / edit form --}}
    <x-gp247::card>
        <x-slot:header>
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                {{ $editingId
                    ? gp247_language_render('action.edit')
                    : gp247_language_render('admin.supplier.add_new_title') }}
            </h3>
            @if ($editingId)
                <x-gp247::button size="sm" variant="secondary" wire:click="resetForm"
                    data-testid="multi-vendor-pro-supplier-cancel">
                    <i class="fas fa-plus"></i> {{ gp247_language_render('admin.add_new') }}
                </x-gp247::button>
            @endif
        </x-slot:header>

        <form wire:submit="save" class="space-y-5">
            <div>
                <label class="{{ $labelCls }}">
                    {{ gp247_language_render('admin.supplier.name') }} <span class="text-red-500">*</span>
                </label>
                <input type="text" class="{{ $inputCls }}" maxlength="100" wire:model="form.name"
                    data-testid="multi-vendor-pro-supplier-name">
                @error('form.name')<p class="{{ $errCls }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelCls }}">{{ strip_tags(gp247_language_render('admin.supplier.alias')) }}</label>
                <input type="text" class="{{ $inputCls }}" maxlength="100" wire:model="form.alias"
                    data-testid="multi-vendor-pro-supplier-alias">
                @error('form.alias')<p class="{{ $errCls }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelCls }}">{{ strip_tags(gp247_language_render('admin.supplier.phone')) }}</label>
                <input type="text" class="{{ $inputCls }}" wire:model="form.phone">
                @error('form.phone')<p class="{{ $errCls }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelCls }}">{{ strip_tags(gp247_language_render('admin.supplier.url')) }}</label>
                <input type="text" class="{{ $inputCls }}" wire:model="form.url">
                @error('form.url')<p class="{{ $errCls }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelCls }}">{{ strip_tags(gp247_language_render('admin.supplier.email')) }}</label>
                <input type="email" class="{{ $inputCls }}" wire:model="form.email">
                @error('form.email')<p class="{{ $errCls }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelCls }}">{{ strip_tags(gp247_language_render('admin.supplier.address')) }}</label>
                <input type="text" class="{{ $inputCls }}" wire:model="form.address">
                @error('form.address')<p class="{{ $errCls }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelCls }}">
                    {{ strip_tags(gp247_language_render('admin.supplier.image')) }} <span class="text-red-500">*</span>
                </label>
                <x-gp247::media-input name="image" type="vendor_supplier" wire:model="form.image" :value="$form['image'] ?? ''" />
                @error('form.image')<p class="{{ $errCls }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelCls }}">{{ strip_tags(gp247_language_render('admin.supplier.sort')) }}</label>
                <input type="number" min="0" class="{{ $inputCls }}" wire:model="form.sort">
                @error('form.sort')<p class="{{ $errCls }}">{{ $message }}</p>@enderror
            </div>

            {{-- Admin-defined custom fields (type = shop_supplier), bound through the
                 HasCustomFields trait state ($customFields keyed by field code). --}}
            @if (is_countable($customFieldDefs) && count($customFieldDefs))
                <div class="space-y-4 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ gp247_language_render('admin.custom_field.title') }}
                    </p>
                    @foreach ($customFieldDefs as $field)
                        @php($opts = json_decode($field->default ?? '', true) ?: [])
                        <div>
                            <label class="{{ $labelCls }}">
                                {{ gp247_language_render($field->name) }}@if ($field->required) <span class="text-red-500">*</span>@endif
                            </label>

                            @switch($field->option)
                                @case('textarea')
                                    <textarea rows="2" class="{{ $inputCls }}" wire:model="customFields.{{ $field->code }}"></textarea>
                                    @break

                                @case('select')
                                    <select class="{{ $inputCls }}" wire:model="customFields.{{ $field->code }}">
                                        <option value="">--</option>
                                        @foreach ($opts as $optVal => $optLabel)
                                            <option value="{{ $optVal }}">{{ $optLabel }}</option>
                                        @endforeach
                                    </select>
                                    @break

                                @case('checkbox')
                                    <div class="flex flex-wrap gap-3">
                                        @foreach ($opts as $optVal => $optLabel)
                                            <x-gp247::checkbox :label="$optLabel"
                                                value="{{ $optVal }}"
                                                wire:model="customFields.{{ $field->code }}" />
                                        @endforeach
                                    </div>
                                    @break

                                @case('radio')
                                    <div class="flex flex-wrap gap-3">
                                        @foreach ($opts as $optVal => $optLabel)
                                            <label class="flex cursor-pointer select-none items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-400">
                                                {{-- WHY inline accent-color: the admin Tailwind bundle ships no accent-* utility. --}}
                                                <input type="radio" value="{{ $optVal }}" wire:model="customFields.{{ $field->code }}"
                                                    style="accent-color:#2563eb" class="cursor-pointer">
                                                <span>{{ $optLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @break

                                @default
                                    {{-- Typed inputs (number/date/email/url/color…) use the option name as the native type. --}}
                                    <input type="{{ $field->option ?: 'text' }}" class="{{ $inputCls }}"
                                        wire:model="customFields.{{ $field->code }}">
                            @endswitch

                            @error('customFields.'.$field->code)<p class="{{ $errCls }}">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="flex justify-end">
                <x-gp247::button type="submit" variant="primary" wire:loading.attr="disabled"
                    data-testid="multi-vendor-pro-supplier-submit">
                    <i class="fas fa-save"></i> {{ gp247_language_render('action.submit') }}
                </x-gp247::button>
            </div>
        </form>
    </x-gp247::card>

    {{-- Right: supplier list --}}
    <x-gp247::card>
        <x-slot:header>
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                {{ gp247_language_render('admin.supplier.list') }}
            </h3>
            <input type="search" wire:model.live.debounce.400ms="keyword" class="{{ $inputCls }} w-48"
                placeholder="{{ gp247_language_render('admin.search') }}"
                data-testid="multi-vendor-pro-supplier-search">
        </x-slot:header>

        <x-gp247::table :empty="$rows->isEmpty() ? gp247_language_render('admin.no_records') : null">
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.supplier.image') }}</th>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('name')">
                        {{ gp247_language_render('admin.supplier.name') }} @if ($sortField === 'name')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.supplier.email') }}</th>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('sort')">
                        {{ gp247_language_render('admin.supplier.sort') }} @if ($sortField === 'sort')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.action') }}</th>
                </tr>
            </x-slot:head>

            @foreach ($rows as $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ (string) $row->id === (string) $editingId ? 'bg-blue-100 border-l-4 border-blue-500 dark:bg-blue-900 dark:border-blue-500' : '' }}"
                    wire:key="sup-{{ $row->id }}" data-testid="multi-vendor-pro-supplier-row">
                    <td class="px-4 py-3">{!! gp247_image_render($row->getThumb(), '46px', '46px', $row->name) !!}</td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $row->name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row->email }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row->sort }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <x-gp247::button size="sm" variant="ghost" wire:click="editRow('{{ $row->id }}')" data-testid="multi-vendor-pro-supplier-list-edit"><i class="fas fa-edit"></i></x-gp247::button>
                            <x-gp247::button size="sm" variant="ghost" wire:click="delete('{{ $row->id }}')" wire:confirm="{{ gp247_language_render('action.delete_confirm') }}"><i class="fas fa-trash-alt text-red-600"></i></x-gp247::button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-gp247::table>

        <div class="mt-4">{{ $rows->links() }}</div>
    </x-gp247::card>
</div>
