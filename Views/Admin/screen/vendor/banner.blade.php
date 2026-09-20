@extends($templatePathAdminVendor.'layout')

@section('main')

<form action="{{ $url_action }}" method="post" accept-charset="UTF-8" id="form-main" enctype="multipart/form-data">
    @csrf

    <x-gp247::card>
        <x-slot:header>
            <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ $title_description ?? '' }}</h3>
            </div>
            <x-gp247::button variant="secondary" size="sm"
                href="{{ gp247_route_admin('vendor_admin_banner.index') }}"
                title="{{ gp247_language_render('admin.back_list') }}">
                <i class="fas fa-list"></i>
                {{ gp247_language_render('admin.back_list') }}
            </x-gp247::button>
        </x-slot:header>

        <div class="space-y-4">
            @include($templatePathAdminVendor.'component.media_input', [
                'name' => 'image',
                'type' => 'vendor_banner',
                'label' => gp247_language_render('admin.banner.image'),
                'value' => old('image', $banner['image'] ?? ''),
                'error' => $errors->first('image'),
            ])

            <x-gp247::input name="url" id="url"
                :label="gp247_language_render('admin.banner.url')"
                :value="old() ? old('url') : ($banner['url'] ?? '')"
                :error="$errors->first('url')" />

            <x-gp247::input name="title" id="title"
                :label="gp247_language_render('admin.banner.title')"
                :value="old() ? old('title') : ($banner['title'] ?? '')"
                :error="$errors->first('title')" />

            <x-gp247::searchable-select name="target"
                :label="gp247_language_render('admin.banner.select_target')"
                :options="collect($arrTarget)->map(fn($v, $k) => ['id' => $k, 'label' => $v])->values()->all()"
                :value="old('target', $banner['target'] ?? '')"
                :error="$errors->first('target')" />

            <div>
                <label for="html" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">HTML</label>
                {{-- Raw HTML snippet: kept as a plain monospace textarea (not the
                     rich editor) because the field is meant to hold markup the
                     vendor writes by hand, exactly as in v1. --}}
                <textarea id="html" name="html" rows="10" spellcheck="false"
                    class="block w-full rounded-lg border px-3 py-2 font-mono text-xs shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100 {{ $errors->has('html') ? 'border-red-400 dark:border-red-500' : 'border-gray-300 dark:border-gray-600' }}">{{ old('html', $banner['html'] ?? '') }}</textarea>
                @if ($errors->has('html'))
                    <p class="mt-1 text-xs text-red-600"><i class="fa fa-info-circle"></i> {{ $errors->first('html') }}</p>
                @endif
            </div>

            @if (!empty($dataType))
            <x-gp247::searchable-select name="type"
                :label="gp247_language_render('admin.banner.type')"
                :options="collect($dataType)->map(fn($name, $key) => ['id' => $key, 'label' => $name])->values()->all()"
                :value="old('type', $banner['type'] ?? '')"
                :clearable="false"
                :error="$errors->first('type')" />
            @endif

            <x-gp247::input name="sort" id="sort" type="number" min="0"
                :label="gp247_language_render('admin.banner.sort')"
                :value="old() ? old('sort') : ($banner['sort'] ?? 0)"
                :error="$errors->first('sort')" />

            <x-gp247::checkbox name="status" value="1"
                :label="gp247_language_render('admin.banner.status')"
                :checked="(bool) old('status', empty($banner['status']) ? 0 : 1)" />
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
