{{--
    MultiVendor admin assets.

    Included by every plugin screen that renders inside core's admin shell
    (gp247-admin::layouts.admin) and by the plugin's own vendor shell. Ships:
      - the plugin-local Tailwind bundle (core's admin.css only contains classes
        core itself uses — see resources/assets/README.md);
      - mvp-admin.js, the jQuery-free replacement for the v1 $.ajax/$.pjax glue;
      - the CSRF token, which core's Livewire-first layout does not expose as a
        <meta> tag.
--}}
@push('styles')
    <link rel="stylesheet" href="{{ gp247_file('GP247/Plugins/MultiVendor/css/admin.css') }}">
@endpush

@push('scripts')
    <script>window.mvpCsrfToken = '{{ csrf_token() }}';</script>
    <script src="{{ gp247_file('GP247/Plugins/MultiVendor/js/mvp-admin.js') }}"></script>
@endpush
