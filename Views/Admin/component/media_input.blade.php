{{--
    Media picker for the VENDOR area.

    core's <x-gp247::media-input> builds its file-manager URL from
    gp247_route_admin('admin.home') — the STAFF admin's LFM. A vendor is
    authenticated on the `vendor` guard and has no staff session, so that URL
    would bounce them to the admin login. This partial is the same control
    pointed at the vendor's own file manager instead, and it reuses the
    delegated `.lfm` click handler in component/script.blade.php (the picker
    contract v1 used: data-input / data-preview / data-type).

    @param string      $name  input name; also used as the element id
    @param string|null $label field label
    @param string      $value current stored path
    @param string      $type  LFM folder category (e.g. vendor_banner)
    @param string|null $error validation message
--}}
@php
    $value = $value ?? '';
    $error = $error ?? null;
    $previewId = 'preview_'.$name;
@endphp

<div>
    @if (!empty($label))
        <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</label>
    @endif

    <div class="flex">
        <input type="text" id="{{ $name }}" name="{{ $name }}" value="{{ $value }}"
            class="block w-full rounded-s-lg border border-e-0 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100 {{ $error ? 'border-red-400 dark:border-red-500' : 'border-gray-300 dark:border-gray-600' }}">

        <button type="button"
            class="lfm inline-flex shrink-0 items-center gap-2 rounded-e-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
            data-input="{{ $name }}" data-preview="{{ $previewId }}" data-type="{{ $type ?? 'other' }}">
            <i class="fa fa-image"></i>
            <span class="hidden sm:inline">{{ gp247_language_render('admin.product.choose_image') }}</span>
        </button>
    </div>

    @if ($error)
        <p class="mt-1 text-xs text-red-600"><i class="fa fa-info-circle"></i> {{ $error }}</p>
    @endif

    <div id="{{ $previewId }}" class="mt-2 flex flex-wrap gap-2">
        @if ($value)
            <img src="{{ gp247_file($value) }}" alt="{{ $label ?? $name }}" class="max-h-24 w-auto rounded border border-gray-200 dark:border-gray-700">
        @endif
    </div>
</div>
