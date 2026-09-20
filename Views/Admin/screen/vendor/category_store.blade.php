@extends($templatePathAdminVendor.'layout')

@section('main')

@php
    $descriptions = $category_store ? $category_store->descriptions->keyBy('lang')->toArray() : [];
@endphp

<form action="{{ $url_action }}" method="post" accept-charset="UTF-8" id="form-main" enctype="multipart/form-data">
    @csrf

    <x-gp247::card>
        <x-slot:header>
            <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ $title_description ?? '' }}</h3>
            </div>
            <x-gp247::button variant="secondary" size="sm"
                href="{{ gp247_route_admin('vendor_admin_category.index') }}"
                title="{{ gp247_language_render('admin.back_list') }}">
                <i class="fas fa-list"></i>
                {{ gp247_language_render('admin.back_list') }}
            </x-gp247::button>
        </x-slot:header>

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
                    :label="gp247_language_render($appPath.'::category_store.title')"
                    name="descriptions[{{ $code }}][title]"
                    id="{{ $code }}__title"
                    :value="old() ? old('descriptions.'.$code.'.title') : ($descriptions[$code]['title'] ?? '')"
                    :error="$errors->first('descriptions.'.$code.'.title')"
                    :help="gp247_language_render('admin.max_c', ['max' => 200])" />

                <x-gp247::input
                    :label="gp247_language_render($appPath.'::category_store.keyword')"
                    name="descriptions[{{ $code }}][keyword]"
                    id="{{ $code }}__keyword"
                    :value="old() ? old('descriptions.'.$code.'.keyword') : ($descriptions[$code]['keyword'] ?? '')"
                    :error="$errors->first('descriptions.'.$code.'.keyword')"
                    :help="gp247_language_render('admin.max_c', ['max' => 200])" />

                <div>
                    <label for="{{ $code }}__description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ gp247_language_render($appPath.'::category_store.description') }}
                    </label>
                    <textarea id="{{ $code }}__description" name="descriptions[{{ $code }}][description]" rows="3"
                        class="block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100 {{ $errors->has('descriptions.'.$code.'.description') ? 'border-red-400 dark:border-red-500' : 'border-gray-300 dark:border-gray-600' }}">{{ old() ? old('descriptions.'.$code.'.description') : ($descriptions[$code]['description'] ?? '') }}</textarea>
                    @if ($errors->has('descriptions.'.$code.'.description'))
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('descriptions.'.$code.'.description') }}</p>
                    @else
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.max_c', ['max' => 300]) }}</p>
                    @endif
                </div>
            </div>
        </div>
        @endforeach

        <div class="space-y-4">
            <x-gp247::input name="alias" id="alias"
                :label="strip_tags(gp247_language_render($appPath.'::category_store.alias'))"
                :value="old('alias', $category_store['alias'] ?? '')"
                :error="$errors->first('alias')" />

            @include($templatePathAdminVendor.'component.media_input', [
                'name' => 'image',
                'type' => 'vendor_category_store',
                'label' => gp247_language_render($appPath.'::category_store.image'),
                'value' => old('image', $category_store['image'] ?? ''),
                'error' => $errors->first('image'),
            ])

            <x-gp247::input name="sort" id="sort" type="number"
                :label="gp247_language_render($appPath.'::category_store.sort')"
                :value="old() ? old('sort') : ($category_store['sort'] ?? 0)"
                :error="$errors->first('sort')" />

            <x-gp247::checkbox name="status" value="1"
                :label="gp247_language_render($appPath.'::category_store.status')"
                :checked="(bool) old('status', empty($category_store['status']) ? 0 : 1)" />
        </div>

        <x-slot:footer>
            <div class="flex items-center justify-between">
                <x-gp247::button type="reset" variant="warning">{{ gp247_language_render('action.reset') }}</x-gp247::button>
                <x-gp247::button type="submit" variant="primary">{{ gp247_language_render('action.submit') }}</x-gp247::button>
            </div>
        </x-slot:footer>
    </x-gp247::card>
</form>

@endsection
