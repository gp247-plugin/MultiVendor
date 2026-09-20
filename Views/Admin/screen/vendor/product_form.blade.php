{{--
    Vendor product form (TailAdmin) — shared by the add and edit screens.

    v1 carried two near-identical 1,100-line copies of this markup, driven by
    jQuery: select2 for every dropdown, iCheck for the promotion toggle, CKEditor
    for the description, and string-concatenated HTML (built in the controller)
    cloned into the DOM to add sub-images / attributes / grouped products. None of
    that exists in core 2.x, so the repeating sections are Alpine `x-for` lists
    over plain arrays, and the field controls are the shared <x-gp247::*> ones.

    @param object|array $product     the record being edited ([] when adding)
    @param int          $productKind GP247_PRODUCT_SINGLE|BUILD|GROUP
    @param string       $urlAction   form target
--}}
@php
    $isEdit = !empty($product) && !is_array($product);
    $descriptions = $isEdit ? $product->descriptions->keyBy('lang')->toArray() : [];
    $vendorLfmPrefix = gp247_route_admin('vendor_admin.home').'/'.config('lfm.url_prefix');

    /** Current value of a scalar product field: old input first, then the record. */
    $val = function (string $field, $default = '') use ($product, $isEdit) {
        return old($field, $isEdit ? ($product->{$field} ?? $default) : $default);
    };

    // Selected categories (multi).
    $selectedCategories = old('category', $isEdit ? $product->categories->pluck('id')->all() : []);

    // Sub-images: old input wins, then the stored gallery.
    $subImages = array_values(array_filter(
        old('sub_image', $isEdit ? $product->images->pluck('image')->all() : [])
    ));

    // Attribute values per group, as [groupId => [['name' => ..., 'add_price' => ...], ...]].
    $attributeRows = [];
    if ($isEdit) {
        foreach ($product->attributes->groupBy('attribute_group_id')->toArray() as $groupId => $rows) {
            foreach ($rows as $row) {
                $attributeRows[$groupId][] = ['name' => $row['name'], 'add_price' => $row['add_price']];
            }
        }
    }
    if (is_array(old('attribute'))) {
        $attributeRows = [];
        foreach (old('attribute') as $groupId => $group) {
            foreach ($group['name'] ?? [] as $i => $name) {
                $attributeRows[$groupId][] = ['name' => $name, 'add_price' => $group['add_price'][$i] ?? 0];
            }
        }
    }

    // Grouped / built products.
    $groupRows = array_values(array_filter(
        old('productInGroup', $isEdit && $productKind == GP247_PRODUCT_GROUP ? $product->groups->pluck('product_id')->all() : [])
    ));

    $buildIds = old('productBuild', $isEdit && $productKind == GP247_PRODUCT_BUILD ? $product->builds->pluck('product_id')->all() : []);
    $buildQty = old('productBuildQty', $isEdit && $productKind == GP247_PRODUCT_BUILD ? $product->builds->pluck('quantity')->all() : []);
    $buildRows = [];
    foreach ((array) $buildIds as $i => $pid) {
        if ($pid) {
            $buildRows[] = ['id' => (string) $pid, 'qty' => (int) ($buildQty[$i] ?? 1)];
        }
    }

    $productOptions = collect($listProductSingle ?? [])
        ->map(fn($v, $k) => ['id' => (string) $k, 'label' => $v['name'] ?? $k])
        ->values()
        ->all();

    $isSingle = $productKind == GP247_PRODUCT_SINGLE;
    $isSingleOrBuild = $isSingle || $productKind == GP247_PRODUCT_BUILD;

    $selectClass = 'block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
@endphp

<form action="{{ $urlAction }}" method="post" name="form_name" accept-charset="UTF-8" id="form-main" enctype="multipart/form-data">
    @csrf
    @unless ($isEdit)
        <input type="hidden" name="kind" value="{{ $productKind }}">
    @endunless

    <x-gp247::card>
        <x-slot:header>
            <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ $title_description ?? '' }}</h3>
                @if (gp247_config_admin('product_kind'))
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        <b>{{ gp247_language_render('product.kind') }}:</b> {{ $kinds[$productKind] ?? '' }}
                    </p>
                @endif
            </div>
            <x-gp247::button variant="secondary" size="sm"
                href="{{ gp247_route_admin('vendor_admin_product.index') }}"
                title="{{ gp247_language_render('admin.back_list') }}">
                <i class="fas fa-list"></i>
                {{ gp247_language_render('admin.back_list') }}
            </x-gp247::button>
        </x-slot:header>

        {{-- Per-language descriptions --}}
        @foreach ($languages as $code => $language)
        <div x-data="{ open: true }" class="mb-5 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                <h4 class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                    {{ $language->name }} {!! gp247_image_render($language->icon, '20px', '20px', $language->name) !!}
                </h4>
                <button type="button" x-on:click="open = !open" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fas" :class="open ? 'fa-minus' : 'fa-plus'"></i>
                </button>
            </div>

            <div class="space-y-4 p-4" x-show="open">
                <x-gp247::input
                    :label="gp247_language_render('product.name')"
                    name="descriptions[{{ $code }}][name]"
                    id="{{ $code }}__name"
                    :value="old('descriptions.'.$code.'.name', $descriptions[$code]['name'] ?? '')"
                    :error="$errors->first('descriptions.'.$code.'.name')" />

                <x-gp247::input
                    :label="gp247_language_render('product.keyword')"
                    name="descriptions[{{ $code }}][keyword]"
                    id="{{ $code }}__keyword"
                    :value="old('descriptions.'.$code.'.keyword', $descriptions[$code]['keyword'] ?? '')"
                    :error="$errors->first('descriptions.'.$code.'.keyword')" />

                <div>
                    <label for="{{ $code }}__description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ gp247_language_render('product.description') }}
                    </label>
                    <textarea id="{{ $code }}__description" name="descriptions[{{ $code }}][description]" rows="3"
                        class="block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100 {{ $errors->has('descriptions.'.$code.'.description') ? 'border-red-400 dark:border-red-500' : 'border-gray-300 dark:border-gray-600' }}">{{ old('descriptions.'.$code.'.description', $descriptions[$code]['description'] ?? '') }}</textarea>
                    @if ($errors->has('descriptions.'.$code.'.description'))
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('descriptions.'.$code.'.description') }}</p>
                    @endif
                </div>

                @if ($isSingleOrBuild)
                    @include($templatePathAdminVendor.'component.rich_editor', [
                        'id' => $code.'__content',
                        'name' => 'descriptions['.$code.'][content]',
                        'label' => gp247_language_render('product.content'),
                        'value' => old('descriptions.'.$code.'.content', $descriptions[$code]['content'] ?? ''),
                        'error' => $errors->first('descriptions.'.$code.'.content'),
                        'lfmPrefix' => $vendorLfmPrefix,
                        'mediaType' => 'vendor_product',
                    ])
                @endif
            </div>
        </div>
        @endforeach

        <div class="space-y-4">

            <x-gp247::searchable-select name="category" multiple
                :label="gp247_language_render('admin.product.select_category')"
                :placeholder="gp247_language_render('admin.product.select_category')"
                :options="collect($categories)->map(fn($v, $k) => ['id' => $k, 'label' => $v])->values()->all()"
                :value="$selectedCategories"
                :error="$errors->first('category')" />

            @if (gp247_config_global('MultiVendor'))
            <div>
                <x-gp247::searchable-select name="vendor_category_id"
                    :label="gp247_language_render('product.category_store')"
                    :placeholder="gp247_language_render('admin.product.select_category')"
                    :options="collect($categoriesStore)->map(fn($v, $k) => ['id' => $k, 'label' => $v])->values()->all()"
                    :value="$val('vendor_category_id')"
                    :error="$errors->first('vendor_category_id')" />
                <a target="_blank" rel="noopener" href="{{ gp247_route_admin('vendor_admin_category.index') }}"
                    class="mt-1 inline-block text-xs font-medium text-blue-600 hover:underline dark:text-blue-400">
                    <i class="fa fa-plus"></i> {{ gp247_language_render('action.add') }}
                </a>
            </div>
            @endif

            {{-- Main image + gallery --}}
            @include($templatePathAdminVendor.'component.media_input', [
                'name' => 'image',
                'type' => 'vendor_product',
                'label' => gp247_language_render('product.image'),
                'value' => $val('image'),
                'error' => $errors->first('image'),
            ])

            <div x-data="{
                    images: @js($subImages),
                    add() { this.images.push(''); },
                    remove(index) { this.images.splice(index, 1); },
                }">
                <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ gp247_language_render('admin.product.add_sub_image') }}
                </span>

                <div class="space-y-3">
                    <template x-for="(image, index) in images" :key="index">
                        <div class="flex items-start gap-2">
                            <div class="flex-1">
                                {{-- The picker writes into the input by id, so each row
                                     needs its own id; Alpine binds it from the index. --}}
                                <div class="flex">
                                    <input type="text" name="sub_image[]" x-model="images[index]"
                                        :id="'sub_image_' + index"
                                        class="block w-full rounded-s-lg border border-e-0 border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                    <button type="button"
                                        class="lfm inline-flex shrink-0 items-center gap-2 bg-blue-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
                                        :data-input="'sub_image_' + index"
                                        :data-preview="'preview_sub_image_' + index"
                                        data-type="vendor_product">
                                        <i class="fa fa-image"></i>
                                    </button>
                                    <button type="button" x-on:click="remove(index)" title="Remove"
                                        class="inline-flex shrink-0 items-center rounded-e-lg bg-red-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                                <div :id="'preview_sub_image_' + index" class="mt-2 flex flex-wrap gap-2">
                                    <template x-if="images[index]">
                                        <img :src="@js(gp247_file('')) + images[index]" class="max-h-24 w-auto rounded border border-gray-200 dark:border-gray-700">
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <x-gp247::button type="button" variant="success" size="sm" class="mt-3" x-on:click="add()">
                    <i class="fa fa-plus"></i> {{ gp247_language_render('admin.product.add_sub_image') }}
                </x-gp247::button>
            </div>

            <x-gp247::input name="sku" id="sku"
                :label="gp247_language_render('product.sku')"
                :value="$val('sku')"
                :error="$errors->first('sku')" />

            <x-gp247::input name="alias" id="alias"
                :label="gp247_language_render('product.alias')"
                :value="$val('alias')"
                :error="$errors->first('alias')" />

            @if (gp247_config_admin('product_brand') && $isSingleOrBuild)
            <x-gp247::searchable-select name="brand_id"
                :label="gp247_language_render('product.brand')"
                :options="collect($brands)->map(fn($v, $k) => ['id' => $k, 'label' => $v])->values()->all()"
                :value="$val('brand_id')"
                :error="$errors->first('brand_id')" />
            @endif

            @if (gp247_config_admin('product_supplier') && $isSingleOrBuild)
            <x-gp247::searchable-select name="supplier_id"
                :label="gp247_language_render('product.supplier')"
                :options="collect($suppliers)->map(fn($v, $k) => ['id' => $k, 'label' => $v])->values()->all()"
                :value="$val('supplier_id')"
                :error="$errors->first('supplier_id')" />
            @endif

            @if (gp247_config_admin('product_cost') && $isSingle)
            <x-gp247::input name="cost" id="cost" type="number" step="0.01" min="0"
                :label="gp247_language_render('product.cost')"
                :value="$val('cost', 0)"
                :error="$errors->first('cost')" />
            @endif

            @if (gp247_config_admin('product_price') && $isSingleOrBuild)
            <x-gp247::input name="price" id="price" type="number" step="0.01" min="0"
                :label="gp247_language_render('product.price')"
                :value="$val('price', 0)"
                :error="$errors->first('price')" />
            @endif

            @if (gp247_config_admin('product_promotion') && $isSingleOrBuild)
            {{-- Promotion price + window, revealed by the toggle (v1 used iCheck events). --}}
            <div x-data="{ use: {{ old('promotion_use', $isEdit && $product->price_promotion !== null ? 'on' : '') === 'on' ? 'true' : 'false' }} }">
                <x-gp247::checkbox name="promotion_use" id="promotion_use" value="on"
                    :label="gp247_language_render('admin.product.promotion_use')"
                    x-model="use" />

                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-3" x-show="use" x-cloak>
                    <x-gp247::input name="price_promotion" id="price_promotion" type="number" step="0.01" min="0"
                        :placeholder="gp247_language_render('product.price_promotion')"
                        :value="$val('price_promotion', 0)" />

                    <x-gp247::input name="price_promotion_start" id="price_promotion_start" type="date"
                        :placeholder="gp247_language_render('product.price_promotion_start')"
                        :value="$val('price_promotion_start')" />

                    <x-gp247::input name="price_promotion_end" id="price_promotion_end" type="date"
                        :placeholder="gp247_language_render('product.price_promotion_end')"
                        :value="$val('price_promotion_end')" />
                </div>

                @if ($errors->has('price_promotion'))
                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('price_promotion') }}</p>
                @endif
            </div>
            @endif

            @if (gp247_config_admin('product_tax') && gp247_config_admin('product_tax') != 'none' && $isSingleOrBuild)
            <x-gp247::searchable-select name="tax_id"
                :label="gp247_language_render('product.tax')"
                :options="collect($taxs)->map(fn($v, $k) => ['id' => $k, 'label' => $v])->values()->all()"
                :value="$val('tax_id')"
                :error="$errors->first('tax_id')" />
            @endif

            @if (gp247_config_admin('product_stock') && $isSingleOrBuild)
            <x-gp247::input name="stock" id="stock" type="number"
                :label="gp247_language_render('product.stock')"
                :value="$val('stock', 0)"
                :error="$errors->first('stock')" />
            @endif

            @if (gp247_config_admin('product_weight') && $isSingleOrBuild)
            <x-gp247::searchable-select name="weight_class"
                :label="gp247_language_render('product.weight_class')"
                :options="collect($listWeight)->map(fn($v) => ['id' => $v, 'label' => $v])->values()->all()"
                :value="$val('weight_class')"
                :clearable="false"
                :error="$errors->first('weight_class')" />

            <x-gp247::input name="weight" id="weight" type="number" step="0.01" min="0"
                :label="gp247_language_render('product.weight')"
                :value="$val('weight', 0)"
                :error="$errors->first('weight')" />
            @endif

            @if (gp247_config_admin('product_length') && $isSingleOrBuild)
            <x-gp247::searchable-select name="length_class"
                :label="gp247_language_render('product.length_class')"
                :options="collect($listLength)->map(fn($v) => ['id' => $v, 'label' => $v])->values()->all()"
                :value="$val('length_class')"
                :clearable="false"
                :error="$errors->first('length_class')" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-gp247::input name="length" id="length" type="number" step="0.01" min="0"
                    :label="gp247_language_render('product.length')"
                    :value="$val('length', 0)"
                    :error="$errors->first('length')" />

                <x-gp247::input name="height" id="height" type="number" step="0.01" min="0"
                    :label="gp247_language_render('product.height')"
                    :value="$val('height', 0)"
                    :error="$errors->first('height')" />

                <x-gp247::input name="width" id="width" type="number" step="0.01" min="0"
                    :label="gp247_language_render('product.width')"
                    :value="$val('width', 0)"
                    :error="$errors->first('width')" />
            </div>
            @endif

            @if (gp247_config_admin('product_tag') && $isSingle)
            {{-- The download path only applies to the "download" tag. --}}
            <div x-data="{ tag: @js((string) $val('tag')) }">
                <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ gp247_language_render('product.tag') }}
                </span>

                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <input type="radio" name="tag" value="" x-model="tag"
                            class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                        None
                    </label>
                    @foreach ($tags as $tag)
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <input type="radio" name="tag" value="{{ $tag }}" x-model="tag"
                            class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                        {{ $tag }}
                    </label>
                    @endforeach
                </div>

                @if ($errors->has('tag'))
                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('tag') }}</p>
                @endif

                <div class="mt-3" id="download_path" x-show="tag === @js((string) GP247_TAG_DOWNLOAD)" x-cloak>
                    <input type="text" name="download_path" value="{{ $val('download_path') }}"
                        placeholder="{{ gp247_language_render('product.download_path') }}"
                        class="{{ $selectClass }}">
                </div>
            </div>
            @endif

            @if (gp247_config_admin('product_available') && $isSingleOrBuild)
            <x-gp247::input name="date_available" id="date_available" type="date"
                :label="gp247_language_render('product.date_available')"
                :value="$val('date_available')"
                :error="$errors->first('date_available')" />
            @endif

            @if ($isSingleOrBuild)
            <x-gp247::input name="minimum" id="minimum" type="number" min="0"
                :label="gp247_language_render('product.minimum')"
                :value="$val('minimum', 0)"
                :error="$errors->first('minimum')" />
            @endif

            <x-gp247::input name="sort" id="sort" type="number" min="0"
                :label="gp247_language_render('product.sort')"
                :value="$val('sort', 0)"
                :error="$errors->first('sort')" />

            <x-gp247::checkbox name="status" value="on"
                :label="gp247_language_render('product.status')"
                :checked="$isEdit ? (bool) old('status', $product->status) : (old() ? old('status') === 'on' : true)" />

            {{-- Attributes --}}
            @if (gp247_config_admin('product_attribute') && $isSingle && !empty($attributeGroup))
            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <p class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ gp247_language_render('product.attribute') }}
                    <a target="_blank" rel="noopener" href="{{ gp247_route_admin('vendor_admin_attribute_group.index') }}"
                        class="text-blue-600 hover:underline dark:text-blue-400"><i class="fa fa-plus"></i></a>
                </p>

                @foreach ($attributeGroup as $attGroupId => $attName)
                <div class="mb-4" x-data="{
                        rows: @js($attributeRows[$attGroupId] ?? []),
                        add() { this.rows.push({ name: '', add_price: 0 }); },
                        remove(index) { this.rows.splice(index, 1); },
                    }">
                    <p class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $attName }}:</p>

                    <div class="space-y-2">
                        <template x-for="(row, index) in rows" :key="index">
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="row.name"
                                    name="attribute[{{ $attGroupId }}][name][]"
                                    placeholder="{{ gp247_language_render('admin.product.add_attribute_place') }}"
                                    class="{{ $selectClass }}">
                                <input type="number" step="0.01" x-model="row.add_price"
                                    name="attribute[{{ $attGroupId }}][add_price][]"
                                    placeholder="{{ gp247_language_render('admin.product.add_price_place') }}"
                                    class="{{ $selectClass }} w-40">
                                <button type="button" x-on:click="remove(index)" title="Remove"
                                    class="inline-flex shrink-0 items-center rounded-lg bg-red-600 px-3 py-2 text-sm text-white transition hover:bg-red-700">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </template>
                    </div>

                    <x-gp247::button type="button" variant="success" size="sm" class="mt-2" x-on:click="add()">
                        <i class="fa fa-plus"></i> {{ gp247_language_render('admin.product.add_attribute') }}
                    </x-gp247::button>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Products that make up a "build" product --}}
            @if ($productKind == GP247_PRODUCT_BUILD)
            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                x-data="{
                    rows: @js($buildRows),
                    add() { this.rows.push({ id: '', qty: 1 }); },
                    remove(index) { this.rows.splice(index, 1); },
                }">
                <p class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ gp247_language_render('admin.product.select_product_in_build') }}
                </p>

                <div class="space-y-2">
                    <template x-for="(row, index) in rows" :key="index">
                        <div class="flex items-center gap-2">
                            <select name="productBuild[]" x-model="row.id" class="{{ $selectClass }}">
                                <option value=""></option>
                                @foreach ($productOptions as $option)
                                    <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            <input type="number" min="1" name="productBuildQty[]" x-model="row.qty" class="{{ $selectClass }} w-28">
                            <button type="button" x-on:click="remove(index)" title="Remove"
                                class="inline-flex shrink-0 items-center rounded-lg bg-red-600 px-3 py-2 text-sm text-white transition hover:bg-red-700">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </template>
                </div>

                <x-gp247::button type="button" variant="success" size="sm" class="mt-2" x-on:click="add()">
                    <i class="fa fa-plus"></i> {{ gp247_language_render('admin.product.add_product') }}
                </x-gp247::button>

                @if ($errors->has('productBuild') || $errors->has('productBuildQty'))
                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('productBuild') ?: $errors->first('productBuildQty') }}</p>
                @endif
            </div>
            @endif

            {{-- Products inside a "group" product --}}
            @if ($productKind == GP247_PRODUCT_GROUP)
            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                x-data="{
                    rows: @js(array_map('strval', $groupRows)),
                    add() { this.rows.push(''); },
                    remove(index) { this.rows.splice(index, 1); },
                }">
                <p class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ gp247_language_render('admin.product.select_product_in_group') }}
                </p>

                <div class="space-y-2">
                    <template x-for="(row, index) in rows" :key="index">
                        <div class="flex items-center gap-2">
                            <select name="productInGroup[]" x-model="rows[index]" class="{{ $selectClass }}">
                                <option value=""></option>
                                @foreach ($productOptions as $option)
                                    <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            <button type="button" x-on:click="remove(index)" title="Remove"
                                class="inline-flex shrink-0 items-center rounded-lg bg-red-600 px-3 py-2 text-sm text-white transition hover:bg-red-700">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </template>
                </div>

                <x-gp247::button type="button" variant="success" size="sm" class="mt-2" x-on:click="add()">
                    <i class="fa fa-plus"></i> {{ gp247_language_render('admin.product.add_product') }}
                </x-gp247::button>

                @if ($errors->has('productInGroup'))
                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('productInGroup') }}</p>
                @endif
            </div>
            @endif

            @include($templatePathAdminVendor.'component.custom_fields', [
                'type' => 'shop_product',
                'object' => $isEdit ? $product : null,
            ])
        </div>

        <x-slot:footer>
            <div class="flex items-center justify-between">
                <x-gp247::button type="reset" variant="warning">{{ gp247_language_render('action.reset') }}</x-gp247::button>
                <x-gp247::button type="submit" variant="primary">{{ gp247_language_render('action.submit') }}</x-gp247::button>
            </div>
        </x-slot:footer>
    </x-gp247::card>
</form>
