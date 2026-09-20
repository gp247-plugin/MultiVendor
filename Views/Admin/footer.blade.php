{{--
    Vendor admin footer (TailAdmin) — same copyright/version content the v1
    AdminLTE `main-footer` carried, restyled with the shell's utilities.
--}}
@if (!gp247_config_admin('ADMIN_FOOTER_OFF'))
<footer class="flex flex-col items-center justify-between gap-1 border-t border-gray-200 px-4 py-3 text-xs text-gray-400 dark:border-gray-700 dark:text-gray-500 sm:flex-row sm:px-6">
    <p>
        Copyright &copy; {{ date('Y') }}
        <a href="{{ config('s-cart.homepage') }}" target="_blank" rel="noopener" class="hover:text-gray-600 dark:hover:text-gray-300">
            S-Cart: {{ config('s-cart.title') }}
        </a>. All rights reserved.
    </p>
    <p><strong>Version</strong> {{ config('s-cart.sub-version') }}</p>
</footer>
@endif
