{{--
    Rich-text field for the plugin's plain (non-Livewire) forms.

    core 2.x ships <x-gp247::rich-editor>, but that component binds through
    $wire and only works inside a Livewire component. These screens are classic
    controller-rendered forms, so this partial drives the very same self-hosted
    TinyMCE build directly against a normal <textarea name="…">, which the form
    then submits as usual. It replaces v1's CKEditor + jQuery adapter (CKEditor
    was loaded from public/vendor/ckeditor and its adapter needs jQuery).

    Uploads keep going through LFM, exactly like v1.

    @param string      $name      textarea name (supports array syntax)
    @param string      $id        element id
    @param string|null $label     field label
    @param string      $value     current HTML
    @param string|null $error     validation message
    @param string      $lfmPrefix admin base URL that owns the file manager
    @param string      $mediaType LFM folder category for in-editor uploads
--}}
@php
    $lfmPrefix = $lfmPrefix ?? (gp247_route_admin('admin.home').'/'.config('lfm.url_prefix'));
    $mediaType = $mediaType ?? 'content';
    $tinymceBase = gp247_file('GP247/Core/AdminShell/vendor/tinymce');
    $error = $error ?? null;
@endphp

@once
@push('scripts')
<script src="{{ gp247_file('GP247/Core/AdminShell/vendor/tinymce/tinymce.min.js') }}"></script>
<script>
(function () {
    'use strict';

    /**
     * Initialise every rich-text textarea on the page. Content is written back
     * into the underlying <textarea> on change and, defensively, once more when
     * the owning form submits — so a click on Save right after typing still
     * posts the latest content.
     */
    window.mvpInitRichEditors = function () {
        if (typeof tinymce === 'undefined') {
            console.error('MultiVendor rich-editor: TinyMCE build not loaded.');
            return;
        }

        document.querySelectorAll('textarea[data-mvp-editor]').forEach(function (el) {
            if (el.dataset.mvpEditorReady === '1') {
                return;
            }
            el.dataset.mvpEditorReady = '1';

            var lfm = el.dataset.lfmPrefix;
            var mediaType = el.dataset.mediaType;

            tinymce.init({
                target: el,
                base_url: el.dataset.tinymceBase,
                suffix: '.min',
                menubar: false,
                promotion: false,
                branding: false,
                height: 420,
                plugins: 'lists link image table code fullscreen',
                toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image table | code fullscreen',
                skin: document.documentElement.classList.contains('dark') ? 'oxide-dark' : 'oxide',
                content_css: document.documentElement.classList.contains('dark') ? 'dark' : 'default',
                setup: function (editor) {
                    // Keep the textarea authoritative: the form posts its value.
                    editor.on('change keyup', function () {
                        editor.save();
                    });
                },
                // Route the editor's file pickers through LFM, as v1 did.
                file_picker_callback: function (callback) {
                    window.open(lfm + '?type=' + encodeURIComponent(mediaType), 'FileManager', 'width=900,height=600');
                    window.SetUrl = function (items) {
                        if (items && items.length) {
                            callback(items[0].url);
                        }
                    };
                },
            });
        });

        // Flush every editor into its textarea before any form submits.
        document.addEventListener('submit', function () {
            tinymce.triggerSave();
        }, true);
    };

    document.addEventListener('DOMContentLoaded', window.mvpInitRichEditors);
})();
</script>
@endpush
@endonce

<div>
    @if (!empty($label))
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</label>
    @endif

    <textarea id="{{ $id }}" name="{{ $name }}"
        data-mvp-editor="1"
        data-lfm-prefix="{{ $lfmPrefix }}"
        data-media-type="{{ $mediaType }}"
        data-tinymce-base="{{ $tinymceBase }}"
        class="block w-full rounded-lg border px-3 py-2 text-sm shadow-sm dark:bg-gray-700 dark:text-gray-100 {{ $error ? 'border-red-400 dark:border-red-500' : 'border-gray-300 dark:border-gray-600' }}">{{ $value ?? '' }}</textarea>

    @if ($error)
        <p class="mt-1 text-xs text-red-600"><i class="fa fa-info-circle"></i> {{ $error }}</p>
    @endif
</div>
