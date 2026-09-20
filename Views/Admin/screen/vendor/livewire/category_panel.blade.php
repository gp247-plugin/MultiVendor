{{--
    Vendor category panel (Pha 2 L1) — two-panel: add/edit form (left) + list
    (right), on the core ResourcePanel base (via VendorResourcePanel) + the shared
    multilingual-descriptions trait. Mirrors the core shop category-manager
    skeleton (P1/P3) but store-scoped to the signed-in vendor, with the vendor
    lang keys, the vendor_category_store LFM media type, and NO store-picker /
    parent select (the vendor category has a single owning store and no parent).

    @aidlc-unit multi-vendor-pro
    @aidlc-story US-multi-vendor-pro-vendor-admin-livewire
    @aidlc-adr multi-vendor_admin-livewire-migration

    State: $rows (AdminVendorCategory paginator); $form, $desc, $editingId,
    $sortField, $sortDir (ResourcePanel). Languages via $this->languages().
--}}
@php($inputCls = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100')
@php($labelCls = 'mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200')
@php($errCls = 'mt-1 text-xs text-red-500')

@php($langTabs = [])
@php(collect($this->languages())->each(function ($lang, $code) use (&$langTabs) { $langTabs[(string) $code] = $lang->name; }))

{{-- Surface validation errors on hidden (per-language) tabs: mark any tab whose
     desc.<code>.* field failed so the red dot shows even when its pane is closed. --}}
@php($tabsWithErrors = array_values(array_intersect(
    array_map('strval', array_keys($langTabs)),
    array_unique(array_map(static fn ($k) => explode('.', $k)[1] ?? '', preg_grep('/^desc\./', $errors->keys())))
)))

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    {{-- Left: add / edit form --}}
    <x-gp247::card>
        <x-slot:header>
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                {{ $editingId
                    ? gp247_language_render('Plugins/MultiVendor::category_store.admin.edit')
                    : gp247_language_render('Plugins/MultiVendor::category_store.admin.add_new_title') }}
            </h3>
            @if ($editingId)
                <x-gp247::button size="sm" variant="secondary" wire:click="resetForm"
                    data-testid="multi-vendor-pro-category-cancel">
                    <i class="fas fa-plus"></i> {{ gp247_language_render('admin.add_new') }}
                </x-gp247::button>
            @endif
        </x-slot:header>

        <form wire:submit="save" class="space-y-5">

            {{-- Per-language title / keyword / description --}}
            <x-gp247::tabs :tabs="$langTabs" :errors="$tabsWithErrors">
                @foreach ($this->languages() as $code => $lang)
                    <div x-show="tab === @js((string) $code)" x-cloak class="space-y-4" wire:key="cat-lang-{{ $code }}">
                        <div>
                            <label class="{{ $labelCls }}">
                                {{ gp247_language_render('Plugins/MultiVendor::category_store.title') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="text" class="{{ $inputCls }}" maxlength="200"
                                wire:model="desc.{{ $code }}.title"
                                data-testid="multi-vendor-pro-category-title">
                            @error('desc.' . $code . '.title')<p class="{{ $errCls }}">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $labelCls }}">{{ gp247_language_render('Plugins/MultiVendor::category_store.keyword') }}</label>
                            <input type="text" class="{{ $inputCls }}" maxlength="200" wire:model="desc.{{ $code }}.keyword">
                            @error('desc.' . $code . '.keyword')<p class="{{ $errCls }}">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $labelCls }}">{{ gp247_language_render('Plugins/MultiVendor::category_store.description') }}</label>
                            <textarea rows="3" class="{{ $inputCls }}" maxlength="300" wire:model="desc.{{ $code }}.description"></textarea>
                            @error('desc.' . $code . '.description')<p class="{{ $errCls }}">{{ $message }}</p>@enderror
                        </div>
                    </div>
                @endforeach
            </x-gp247::tabs>

            {{-- Alias is global (not per-language) → outside the tabs. --}}
            <div>
                <label class="{{ $labelCls }}">{{ strip_tags(gp247_language_render('Plugins/MultiVendor::category_store.alias')) }}</label>
                <input type="text" class="{{ $inputCls }}" maxlength="100" wire:model="form.alias"
                    data-testid="multi-vendor-pro-category-alias">
                @error('form.alias')<p class="{{ $errCls }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelCls }}">{{ gp247_language_render('Plugins/MultiVendor::category_store.image') }}</label>
                <x-gp247::media-input name="image" type="vendor_category_store" wire:model="form.image" :value="$form['image'] ?? ''" />
            </div>

            <div>
                <label class="{{ $labelCls }}">{{ gp247_language_render('Plugins/MultiVendor::category_store.sort') }}</label>
                <input type="number" min="0" class="{{ $inputCls }}" wire:model="form.sort">
                @error('form.sort')<p class="{{ $errCls }}">{{ $message }}</p>@enderror
            </div>

            <x-gp247::checkbox name="status" value="1" wire:model="form.status"
                :label="gp247_language_render('Plugins/MultiVendor::category_store.status')" />

            <div class="flex justify-end">
                <x-gp247::button type="submit" variant="primary" wire:loading.attr="disabled"
                    data-testid="multi-vendor-pro-category-submit">
                    <i class="fas fa-save"></i> {{ gp247_language_render('action.submit') }}
                </x-gp247::button>
            </div>
        </form>
    </x-gp247::card>

    {{-- Right: category list --}}
    <x-gp247::card>
        <x-slot:header>
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                {{ gp247_language_render('Plugins/MultiVendor::category_store.admin.list') }}
            </h3>
            <input type="search" wire:model.live.debounce.400ms="keyword" class="{{ $inputCls }} w-48"
                placeholder="{{ gp247_language_render('admin.search') }}"
                data-testid="multi-vendor-pro-category-search">
        </x-slot:header>

        <x-gp247::table :empty="$rows->isEmpty() ? gp247_language_render('admin.no_records') : null">
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('Plugins/MultiVendor::category_store.image') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('Plugins/MultiVendor::category_store.title') }}</th>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('sort')">
                        {{ gp247_language_render('Plugins/MultiVendor::category_store.sort') }} @if ($sortField === 'sort')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('Plugins/MultiVendor::category_store.status') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.action') }}</th>
                </tr>
            </x-slot:head>

            @foreach ($rows as $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ (string) $row->id === (string) $editingId ? 'bg-blue-100 border-l-4 border-blue-500 dark:bg-blue-900 dark:border-blue-500' : '' }}"
                    wire:key="cat-{{ $row->id }}" data-testid="multi-vendor-pro-category-row">
                    <td class="px-4 py-3">{!! gp247_image_render($row->getThumb(), '40px', '40px', $row->getTitle()) !!}</td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $row->getTitle() ?: $row->alias }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row->sort }}</td>
                    <td class="px-4 py-3"><x-gp247::badge :color="$row->status ? 'green' : 'gray'">{{ $row->status ? gp247_language_render('admin.active') : gp247_language_render('admin.inactive') }}</x-gp247::badge></td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <x-gp247::button size="sm" variant="ghost" wire:click="editRow('{{ $row->id }}')" data-testid="multi-vendor-pro-category-list-edit"><i class="fas fa-edit"></i></x-gp247::button>
                            <x-gp247::button size="sm" variant="ghost" wire:click="delete('{{ $row->id }}')" wire:confirm="{{ gp247_language_render('action.delete_confirm') }}"><i class="fas fa-trash-alt text-red-600"></i></x-gp247::button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-gp247::table>

        <div class="mt-4">{{ $rows->links() }}</div>
    </x-gp247::card>
</div>
