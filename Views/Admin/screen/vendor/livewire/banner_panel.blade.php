{{--
    Vendor banner panel (Pha 2 L1) — two-panel: add/edit form (left) + list
    (right), on the core ResourcePanel base (via VendorResourcePanel). Mirrors the
    core admin two-panel skeleton (P1/P3) but store-scoped to the signed-in vendor.
    Banner is FLAT (no per-language descriptions), so there are no language tabs —
    the fields bind straight to form.*.

    @aidlc-unit multi-vendor-pro
    @aidlc-story US-multi-vendor-pro-vendor-admin-livewire
    @aidlc-adr multi-vendor_admin-livewire-migration

    State: $rows (FrontBanner paginator); $form, $editingId, $keyword, $sortField,
    $sortDir (ResourcePanel). Type/target options via $this->dataType()/arrTarget().
--}}
@php($inputCls = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100')
@php($labelCls = 'mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200')
@php($errCls = 'mt-1 text-xs text-red-500')

@php($dataType = $this->dataType())
@php($arrTarget = $this->arrTarget())

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    {{-- Left: add / edit form --}}
    <x-gp247::card>
        <x-slot:header>
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                {{ $editingId
                    ? gp247_language_render('action.edit')
                    : gp247_language_render('admin.banner.add_new') }}
            </h3>
            @if ($editingId)
                <x-gp247::button size="sm" variant="secondary" wire:click="resetForm"
                    data-testid="multi-vendor-pro-banner-cancel">
                    <i class="fas fa-plus"></i> {{ gp247_language_render('admin.add_new') }}
                </x-gp247::button>
            @endif
        </x-slot:header>

        <form wire:submit="save" class="space-y-5">
            <div>
                <label class="{{ $labelCls }}">{{ gp247_language_render('admin.banner.image') }}</label>
                <x-gp247::media-input name="image" type="vendor_banner" wire:model="form.image" :value="$form['image'] ?? ''" />
            </div>

            <div>
                <label class="{{ $labelCls }}">{{ gp247_language_render('admin.banner.url') }}</label>
                <input type="text" class="{{ $inputCls }}" wire:model="form.url"
                    data-testid="multi-vendor-pro-banner-url">
                @error('form.url')<p class="{{ $errCls }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelCls }}">{{ gp247_language_render('admin.banner.title') }}</label>
                <input type="text" class="{{ $inputCls }}" wire:model="form.name"
                    data-testid="multi-vendor-pro-banner-title">
                @error('form.name')<p class="{{ $errCls }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelCls }}">{{ gp247_language_render('admin.banner.select_target') }}</label>
                <select class="{{ $inputCls }}" wire:model="form.target"
                    data-testid="multi-vendor-pro-banner-target">
                    @foreach ($arrTarget as $value => $text)
                        <option value="{{ $value }}">{{ $text }}</option>
                    @endforeach
                </select>
                @error('form.target')<p class="{{ $errCls }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelCls }}">HTML</label>
                {{-- Raw HTML snippet: a plain monospace textarea (not the rich
                     editor) because the field holds markup the vendor writes by
                     hand, exactly as in v1. --}}
                <textarea rows="10" spellcheck="false"
                    class="{{ $inputCls }} font-mono text-xs" wire:model="form.html"></textarea>
                @error('form.html')<p class="{{ $errCls }}">{{ $message }}</p>@enderror
            </div>

            @if (!empty($dataType))
            <div>
                <label class="{{ $labelCls }}">{{ gp247_language_render('admin.banner.type') }}</label>
                <select class="{{ $inputCls }}" wire:model="form.type"
                    data-testid="multi-vendor-pro-banner-type">
                    @foreach ($dataType as $key => $text)
                        <option value="{{ $key }}">{{ $text }}</option>
                    @endforeach
                </select>
                @error('form.type')<p class="{{ $errCls }}">{{ $message }}</p>@enderror
            </div>
            @endif

            <div>
                <label class="{{ $labelCls }}">{{ gp247_language_render('admin.banner.sort') }}</label>
                <input type="number" min="0" class="{{ $inputCls }}" wire:model="form.sort">
                @error('form.sort')<p class="{{ $errCls }}">{{ $message }}</p>@enderror
            </div>

            <x-gp247::checkbox name="status" value="1" wire:model="form.status"
                :label="gp247_language_render('admin.banner.status')" />

            <div class="flex justify-end">
                <x-gp247::button type="submit" variant="primary" wire:loading.attr="disabled"
                    data-testid="multi-vendor-pro-banner-submit">
                    <i class="fas fa-save"></i> {{ gp247_language_render('action.submit') }}
                </x-gp247::button>
            </div>
        </form>
    </x-gp247::card>

    {{-- Right: banner list --}}
    <x-gp247::card>
        <x-slot:header>
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                {{ gp247_language_render('admin.banner.list') }}
            </h3>
            <input type="search" wire:model.live.debounce.400ms="keyword" class="{{ $inputCls }} w-48"
                placeholder="{{ gp247_language_render('admin.search') }}"
                data-testid="multi-vendor-pro-banner-search">
        </x-slot:header>

        <x-gp247::table :empty="$rows->isEmpty() ? gp247_language_render('admin.no_records') : null">
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.banner.image') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.banner.title') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.banner.url') }}</th>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('sort')">
                        {{ gp247_language_render('admin.banner.sort') }} @if ($sortField === 'sort')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.banner.status') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.banner.click') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.banner.target') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.banner.type') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.action') }}</th>
                </tr>
            </x-slot:head>

            @foreach ($rows as $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ (string) $row->id === (string) $editingId ? 'bg-blue-100 border-l-4 border-blue-500 dark:bg-blue-900 dark:border-blue-500' : '' }}"
                    wire:key="banner-{{ $row->id }}" data-testid="multi-vendor-pro-banner-row">
                    <td class="px-4 py-3">{!! gp247_image_render($row->getThumb(), '', '50px', 'Banner') !!}</td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $row->name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row->url }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row->sort }}</td>
                    <td class="px-4 py-3"><x-gp247::badge :color="$row->status ? 'green' : 'gray'">{{ $row->status ? gp247_language_render('admin.active') : gp247_language_render('admin.inactive') }}</x-gp247::badge></td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ number_format($row->click) }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row->target }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $dataType[$row->type] ?? 'N/A' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <x-gp247::button size="sm" variant="ghost" wire:click="editRow('{{ $row->id }}')" data-testid="multi-vendor-pro-banner-list-edit"><i class="fas fa-edit"></i></x-gp247::button>
                            <x-gp247::button size="sm" variant="ghost" wire:click="delete('{{ $row->id }}')" wire:confirm="{{ gp247_language_render('action.delete_confirm') }}"><i class="fas fa-trash-alt text-red-600"></i></x-gp247::button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-gp247::table>

        <div class="mt-4">{{ $rows->links() }}</div>
    </x-gp247::card>
</div>
