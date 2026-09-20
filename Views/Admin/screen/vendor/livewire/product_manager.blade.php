{{--
    Vendor product manager (MultiVendor, Pha 2 L1 "inherit core layout") — a
    verbatim copy of the core shop product-manager two-panel view (tabbed add/edit
    form on the left + store-scoped list on the right), rendered by
    VendorProductManager which extends the core ProductManager. The only vendor
    additions are the required `vendor_category_id` picker in the General tab and the
    multi-vendor-pro-* test ids; the root-only store-picker guard is kept as-is (it
    auto-hides for a vendor, whose context is never the root scope). All tabs, Kind
    buttons, attribute / composition / custom-field sections and core partials are
    inherited unchanged. TailAdmin-first, no jQuery (rule ui-tailadmin P1/P2).

    @aidlc-unit multi-vendor-pro
    @aidlc-story US-multi-vendor-pro-vendor-admin-livewire
    @aidlc-adr multi-vendor_admin-livewire-migration

    Variables: $rows (paginator); $form, $desc, $customFields, $editingId, $filter* (state).
--}}
@php($inputCls = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100')
@php($labelCls = 'mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200')

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    {{-- ===================== LEFT: FORM ===================== --}}
    <x-gp247::card :title="gp247_language_render($editingId ? 'action.edit' : 'admin.product.add_new')">
        <form wire:submit="save" class="space-y-4">
            {{-- WHY: safety net — some validation errors (e.g. cross-store brand/tax refs from
                 assertSameStoreRefs) land on wire:ignore'd searchable-selects whose message is
                 easy to miss, so the save appears to fail silently (only the tab dot lights up).
                 A top-of-form summary guarantees every error is visible whatever the active tab. --}}
            @if ($errors->any())
                <div class="rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300" data-testid="multi-vendor-pro-product-form-error-summary">
                    <p class="mb-1 flex items-center gap-2 font-semibold">
                        <i class="fas fa-exclamation-circle"></i> {{ gp247_language_quickly('admin.validate_error', 'Please review and fix the fields below.') }}
                    </p>
                    <ul class="list-inside list-disc space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            {{-- WHY: when "Use STRUCTURE TYPE" (product_kind) is disabled in Shop Config, the kind is
                 always SINGLE — hide the selector and let productAttributes() enforce it on save.
                 null (config absent / not yet seeded) is treated as enabled (same as seeder default=1). --}}
            @php($cfgProductKind = function_exists('gp247_config') ? gp247_config('product_kind') : null)
            @php($useStructureType = $cfgProductKind !== '0' && $cfgProductKind !== 0)
            @php($kind = $useStructureType ? (int) ($form['kind'] ?? GP247_PRODUCT_SINGLE) : GP247_PRODUCT_SINGLE)
            @php($isSingle = $kind === GP247_PRODUCT_SINGLE)
            @php($isBuild = $kind === GP247_PRODUCT_BUILD)
            @php($isGroup = $kind === GP247_PRODUCT_GROUP)
            {{-- WHY: gallery + pricing folded into General per UX request; variants/composition still gated by kind. --}}
            @php($tabsMap = array_filter([
                'general' => gp247_language_render('admin.product.tab_general'),
                'desc' => gp247_language_render('admin.product.tab_description'),
                'custom' => gp247_language_render('admin.custom_field.title'),
                'variants' => ($isSingle && $this->productFieldEnabled('product_attribute')) ? gp247_language_render('admin.product_attribute_group.list') : null,
                'composition' => ($isBuild || $isGroup) ? gp247_language_render('product.product') : null,
            ]))
            {{-- WHY: surface validation errors on hidden tabs by mapping each error key to its owning tab. --}}
            @php($tabsWithErrors = array_values(array_intersect(array_keys($tabsMap), array_unique(array_map(static fn ($k) => $k === 'form.alias' || str_starts_with($k, 'desc.') ? 'desc' : (str_starts_with($k, 'customFields') ? 'custom' : (str_starts_with($k, 'attributes') || str_starts_with($k, 'variants') ? 'variants' : (str_starts_with($k, 'buildItems') || str_starts_with($k, 'groupItems') ? 'composition' : 'general'))), $errors->keys())))))
            <x-gp247::tabs :tabs="$tabsMap" :errors="$tabsWithErrors" default="general">

                {{-- ---- General ---- (field visibility mirrors legacy product_add gating by kind) --}}
                <div x-show="tab === 'general'" class="space-y-4">
                    {{-- Store first (ADR admin-shell_store-scoped-resource-panel): the store owns
                         the product and scopes category/brand/tax below, so the user picks it up
                         front; changing it resets those fields + toasts. Only when
                         multi-store/multi-vendor is installed AND the root admin is logged in —
                         a vendor is never the root scope, so this chrome auto-hides for them. --}}
                    @if ($this->storeScopeUiVisible())
                        <div class="rounded-lg border border-blue-200 bg-blue-50/60 p-3 dark:border-blue-900 dark:bg-blue-900/10">
                            <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                {{ gp247_language_render('admin.store.scope_label') }}
                            </label>
                            @if ($this->showStorePicker())
                                <select wire:model.live="formStoreId" data-testid="multi-vendor-pro-product-store-select"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                    <option value="">— {{ gp247_language_render('admin.store.select_store') }} —</option>
                                    @foreach ($this->storeOptions() as $sid => $stitle)
                                        <option value="{{ $sid }}">{{ $stitle }}</option>
                                    @endforeach
                                </select>
                                @error('formStoreId') <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            @else
                                <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    <i class="fas fa-store text-gray-400"></i> {{ $this->currentStoreLabel() }}
                                </div>
                            @endif
                        </div>
                    @endif
                    @if($useStructureType)
                        {{-- WHY: 3-way radio cards beat a dropdown — the kind drives form layout below, so the choice must be immediately scannable. --}}
                        <div>
                            <label class="{{ $labelCls }}">{{ gp247_language_render('product.kind') }}</label>
                            @php($kindOptions = [
                                GP247_PRODUCT_SINGLE => ['label' => gp247_language_render('product.single'), 'icon' => 'fa-cube', 'color' => 'blue'],
                                GP247_PRODUCT_BUILD => ['label' => gp247_language_render('product.build'), 'icon' => 'fa-cubes', 'color' => 'amber'],
                                GP247_PRODUCT_GROUP => ['label' => gp247_language_render('product.group'), 'icon' => 'fa-layer-group', 'color' => 'purple'],
                            ])
                            {{-- WHY: flex+flex-1 keeps all 3 cards on a single row regardless of label length; whitespace-nowrap prevents intra-card wrapping. --}}
                            <div class="flex flex-nowrap gap-2">
                                @foreach ($kindOptions as $val => $opt)
                                    @php($active = $kind === $val)
                                    <label class="flex flex-1 min-w-0 cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-lg border-2 px-3 py-2 text-sm font-medium transition-colors {{ $active ? 'border-' . $opt['color'] . '-500 bg-' . $opt['color'] . '-50 text-' . $opt['color'] . '-700 dark:border-' . $opt['color'] . '-400 dark:bg-' . $opt['color'] . '-900 dark:text-' . $opt['color'] . '-200' : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                                        <input type="radio" wire:model.live="form.kind" value="{{ $val }}" class="sr-only">
                                        <i class="fas {{ $opt['icon'] }}"></i><span class="truncate">{{ $opt['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <x-gp247::input :label="gp247_language_render('product.sku')" name="sku"
                        wire:model="form.sku" :error="$errors->first('form.sku')" required data-testid="multi-vendor-pro-product-sku" />

                    {{-- wire:key on $formStoreId: searchable-select is wire:ignore'd, so a
                         store change must REPLACE it to reload store-scoped category options
                         (ADR admin-shell_store-scoped-resource-panel). --}}
                    <div wire:key="prod-category-{{ $formStoreId }}">
                        <x-gp247::searchable-select
                            model="form.category"
                            :label="gp247_language_render('product.category')"
                            :multiple="true"
                            :options="collect($this->categoryOptions())->map(fn ($title, $id) => ['id' => (string) $id, 'label' => $title])->values()->all()"
                            :error="$errors->first('form.category')"
                            :required="true"
                            data-testid="multi-vendor-pro-product-category"
                        />
                    </div>

                    {{-- Vendor category (MultiVendor extra): a required 1-1 vendor-owned
                         category, stored in vendor_product_category. Only shown when the
                         marketplace is active, matching VendorProductManager::rules(). --}}
                    @if (function_exists('gp247_config_global') && gp247_config_global('MultiVendor'))
                        <div wire:key="prod-vendor-category">
                            <x-gp247::searchable-select
                                model="form.vendor_category_id"
                                :label="gp247_language_render('product.category_store')"
                                :options="collect($this->vendorCategoryOptions())->map(fn ($title, $id) => ['id' => (string) $id, 'label' => $title])->values()->all()"
                                :error="$errors->first('form.vendor_category_id')"
                                :required="true"
                                data-testid="multi-vendor-pro-product-vendor-category"
                            />
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-3">
                        @if (($isSingle || $isBuild) && $this->productFieldEnabled('product_price'))
                            <x-gp247::input type="number" step="0.01" :label="gp247_language_render('product.price')" name="price" wire:model="form.price" :error="$errors->first('form.price')" :help="gp247_money_hint()" />
                        @endif
                        @if ($isSingle && $this->productFieldEnabled('product_cost'))
                            <x-gp247::input type="number" step="0.01" :label="gp247_language_render('product.cost')" name="cost" wire:model="form.cost" :error="$errors->first('form.cost')" :help="gp247_money_hint()" />
                        @endif
                        @if (($isSingle || $isBuild) && $this->productFieldEnabled('product_stock'))
                            <x-gp247::input type="number" min="0" :step="gp247_qty_decimal_enabled() ? '0.01' : '1'" :label="gp247_language_render('product.stock')" name="stock" wire:model="form.stock" :error="$errors->first('form.stock')" />
                        @endif
                        <x-gp247::input type="number" :label="gp247_language_render('product.sort')" name="sort" wire:model="form.sort" :error="$errors->first('form.sort')" />
                    </div>

                    @if ($isSingle || $isBuild)
                        <div class="grid grid-cols-2 gap-3">
                            @if ($this->productFieldEnabled('product_brand'))
                                <div wire:key="prod-brand-{{ $formStoreId }}">
                                    <x-gp247::searchable-select
                                        model="form.brand_id"
                                        :label="gp247_language_render('product.brand')"
                                        :options="collect($this->brandOptions())->map(fn ($name, $id) => ['id' => (string) $id, 'label' => $name])->values()->all()"
                                        :error="$errors->first('form.brand_id')"
                                    />
                                </div>
                            @endif
                            @if ($this->productFieldEnabled('product_supplier'))
                                <x-gp247::searchable-select
                                    model="form.supplier_id"
                                    :label="gp247_language_render('product.supplier')"
                                    :options="collect($this->supplierOptions())->map(fn ($name, $id) => ['id' => (string) $id, 'label' => $name])->values()->all()"
                                    :error="$errors->first('form.supplier_id')"
                                />
                            @endif
                            {{-- Tax has no on/off toggle in Shop Config (it is configured via the
                                 tax-config select there), so it is always shown for single/bundle. --}}
                            <div wire:key="prod-tax-{{ $formStoreId }}">
                                <x-gp247::searchable-select
                                    model="form.tax_id"
                                    :label="gp247_language_render('product.tax')"
                                    :options="collect($this->taxOptions())->map(fn ($name, $id) => ['id' => (string) $id, 'label' => $name])->values()->all()"
                                    :error="$errors->first('form.tax_id')"
                                />
                            </div>
                        </div>
                    @endif

                    <x-gp247::media-input :working-store="$formStoreId ?? ''" :label="gp247_language_render('product.image')" name="image" type="product"
                        wire:model="form.image" :value="$form['image'] ?? ''" :error="$errors->first('form.image')" />

                    {{-- Gallery folded into General (no dedicated tab). --}}
                    @include('gp247-shop-admin::partials.product-images', ['inputCls' => $inputCls])

                    {{-- Pricing extras folded into General for SINGLE/BUILD (legacy parity). --}}
                    @if ($isSingle || $isBuild)
                        @include('gp247-shop-admin::partials.product-pricing', ['inputCls' => $inputCls])
                    @endif

                    {{-- Keyword tags apply to EVERY kind (incl. group/build); gated by product_tags. --}}
                    @include('gp247-shop-admin::partials.product-tags', ['inputCls' => $inputCls])

                    <div class="flex flex-wrap gap-4">
                        <x-gp247::checkbox :label="gp247_language_render('admin.active')" wire:model="form.status" value="1" />
                        <x-gp247::checkbox :label="gp247_language_render('product.approve')" wire:model="form.approve" value="1" />
                    </div>
                </div>

                {{-- ---- Descriptions (per language) ---- --}}
                <div x-show="tab === 'desc'" x-cloak class="space-y-4">
                    {{-- WHY: alias (URL slug) is derived from the product name, so it lives at the top of Description where names are entered. --}}
                    <x-gp247::input :label="gp247_language_render('product.alias')" name="alias"
                        wire:model="form.alias" :error="$errors->first('form.alias')" />

                    @foreach ($this->languages() as $code => $lang)
                        <div class="space-y-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700" wire:key="product-lang-{{ $code }}">
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                                @if ($lang->icon)
                                    {!! gp247_image_render($lang->icon, '20px', '20px', $lang->name) !!}
                                @endif
                                {{ $lang->name }}
                            </h3>
                            <x-gp247::input :label="gp247_language_render('product.name')" name="name_{{ $code }}"
                                wire:model="desc.{{ $code }}.name" :error="$errors->first('desc.' . $code . '.name')" required />
                            <x-gp247::input :label="gp247_language_render('product.keyword')" name="keyword_{{ $code }}"
                                wire:model="desc.{{ $code }}.keyword" :error="$errors->first('desc.' . $code . '.keyword')" />
                            <div>
                                <label class="{{ $labelCls }}">{!! gp247_language_render('product.description') !!}</label>
                                <textarea wire:model="desc.{{ $code }}.description" rows="2" class="{{ $inputCls }}"></textarea>
                                @error('desc.' . $code . '.description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <x-gp247::rich-editor :label="gp247_language_render('product.content')" type="product"
                                model="desc.{{ $code }}.content" :error="$errors->first('desc.' . $code . '.content')" />
                        </div>
                    @endforeach
                </div>

                {{-- ---- Variants (SINGLE only, legacy parity; gated by product_attribute config) ---- --}}
                @if ($isSingle && $this->productFieldEnabled('product_attribute'))
                    <div x-show="tab === 'variants'" x-cloak>
                        @include('gp247-shop-admin::partials.product-variants', ['inputCls' => $inputCls, 'labelCls' => $labelCls])
                    </div>
                @endif

                {{-- ---- Composition (BUILD / GROUP) ---- --}}
                @if ($isBuild || $isGroup)
                    <div x-show="tab === 'composition'" x-cloak>
                        @include('gp247-shop-admin::partials.product-composition', ['inputCls' => $inputCls])
                    </div>
                @endif

                {{-- ---- Custom fields ---- --}}
                <div x-show="tab === 'custom'" x-cloak class="space-y-3">
                    @php($customDefs = $this->customFieldList())
                    @if (is_countable($customDefs) && count($customDefs))
                        @foreach ($customDefs as $field)
                            @php($opts = json_decode($field->default ?? '', true) ?: [])
                            <div>
                                <label class="{{ $labelCls }}">{{ gp247_language_render($field->name) }}@if ($field->required) <span class="text-red-500">*</span>@endif</label>
                                @switch($field->option)
                                    @case('textarea')
                                        <textarea wire:model="customFields.{{ $field->code }}" rows="2" class="{{ $inputCls }}"></textarea>
                                        @break
                                    @case('select')
                                        <select wire:model="customFields.{{ $field->code }}" class="{{ $inputCls }}">
                                            <option value="">--</option>
                                            @foreach ($opts as $optVal => $optLabel)<option value="{{ $optVal }}">{{ $optLabel }}</option>@endforeach
                                        </select>
                                        @break
                                    @case('checkbox')
                                        <div class="flex flex-wrap gap-3">
                                            @foreach ($opts as $optVal => $optLabel)
                                                <x-gp247::checkbox :label="$optLabel" wire:model="customFields.{{ $field->code }}" value="{{ $optVal }}" />
                                            @endforeach
                                        </div>
                                        @break
                                    @case('radio')
                                        <div class="flex flex-wrap gap-3">
                                            @foreach ($opts as $optVal => $optLabel)
                                                <label class="flex cursor-pointer select-none items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-400">
                                                    {{-- WHY: core admin Tailwind bundle ships no accent-* utility (not in
                                                         scan/safelist), so the brand accent is set inline to render blue
                                                         without a rebuild; layout stays on already-built classes. --}}
                                                    <input type="radio" wire:model="customFields.{{ $field->code }}"
                                                        value="{{ $optVal }}" style="accent-color:#2563eb" class="cursor-pointer">
                                                    <span>{{ $optLabel }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        @break
                                    @default
                                        {{-- Typed inputs (number/date/month/week/time/email/password/url/color) use
                                             the option name as the native input type; unknown/empty falls back to text. --}}
                                        <input type="{{ $field->option ?: 'text' }}" wire:model="customFields.{{ $field->code }}" class="{{ $inputCls }}">
                                @endswitch
                                @error('customFields.' . $field->code)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.no_records') }}</p>
                    @endif
                </div>
            </x-gp247::tabs>

            <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
                <x-gp247::button variant="secondary" wire:click="cancelEdit" data-testid="multi-vendor-pro-product-form-cancel">{{ gp247_language_render($editingId ? 'admin.cancel' : 'admin.reset') }}</x-gp247::button>
                <x-gp247::button type="submit" wire:loading.attr="disabled" wire:target="save" data-testid="multi-vendor-pro-product-form-submit">
                    <i class="fas fa-save"></i> {{ gp247_language_render($editingId ? 'admin.update' : 'admin.submit') }}
                </x-gp247::button>
            </div>
        </form>
    </x-gp247::card>

    {{-- ===================== RIGHT: LIST ===================== --}}
    <x-gp247::card :title="gp247_language_render('admin.product.list')">
        <div class="mb-3 grid grid-cols-1 gap-3 lg:grid-cols-2">
            <input type="search" wire:model.live.debounce.300ms="keyword" placeholder="{{ gp247_language_render('search.placeholder') }}" class="{{ $inputCls }}" data-testid="multi-vendor-pro-product-search">
            <select wire:model.live="filterCategory" class="{{ $inputCls }}">
                <option value="">{{ gp247_language_render('product.category') }}</option>
                @foreach ($this->filterCategoryOptions() as $id => $title)<option value="{{ $id }}">{{ $title }}</option>@endforeach
            </select>
        </div>

        <x-gp247::table :empty="$rows->isEmpty() ? gp247_language_render('admin.no_records') : null">
            <x-slot:head>
                <tr>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('sku')">{{ gp247_language_render('product.sku') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('product.name') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('product.kind') }}</th>
                    <th class="cursor-pointer px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('price')">{{ gp247_language_render('product.price') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.status') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.action') }}</th>
                </tr>
            </x-slot:head>

            @foreach ($rows as $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ (string) $row->id === (string) $editingId ? 'bg-blue-100 border-l-4 border-blue-500 dark:bg-blue-900 dark:border-blue-500' : '' }}" wire:key="product-{{ $row->id }}" data-testid="multi-vendor-pro-product-list-item">
                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $row->sku }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                        {{ $row->getName() ?: $row->alias }}
                        @if ($this->storeScopeUiVisible())
                            <span class="mt-0.5 block text-xs text-gray-400 dark:text-gray-500">
                                <i class="fas fa-store"></i> {{ $this->storeLabel($row->store_id) }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @php($rowKind = (int) $row->kind)
                        @php($rowKindBadge = match ($rowKind) {
                            GP247_PRODUCT_BUILD => ['color' => 'amber', 'label' => gp247_language_render('product.build')],
                            GP247_PRODUCT_GROUP => ['color' => 'purple', 'label' => gp247_language_render('product.group')],
                            default => ['color' => 'blue', 'label' => gp247_language_render('product.single')],
                        })
                        <x-gp247::badge :color="$rowKindBadge['color']">{{ $rowKindBadge['label'] }}</x-gp247::badge>
                    </td>
                    <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300">{{ gp247_currency_render($row->price, '', '', '', false) }}</td>
                    <td class="px-4 py-3"><x-gp247::badge :color="$row->status ? 'green' : 'gray'">{{ $row->status ? gp247_language_render('admin.active') : gp247_language_render('admin.inactive') }}</x-gp247::badge></td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <x-gp247::button size="sm" variant="ghost" wire:click="editRow('{{ $row->id }}')" data-testid="multi-vendor-pro-product-list-edit"><i class="fas fa-edit"></i></x-gp247::button>
                            <x-gp247::button size="sm" variant="ghost" wire:click="delete('{{ $row->id }}')" wire:confirm="{{ gp247_language_render('action.delete_confirm') }}" data-testid="multi-vendor-pro-product-list-delete"><i class="fas fa-trash-alt text-red-600"></i></x-gp247::button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-gp247::table>

        <div class="mt-4">{{ $rows->links('gp247-admin::partials.pagination') }}</div>
    </x-gp247::card>
</div>
