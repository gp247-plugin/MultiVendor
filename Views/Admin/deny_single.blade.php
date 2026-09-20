{{-- Inline 403 fragment embedded inside another screen (no layout). --}}
<div class="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20">
    <h2 class="text-sm font-semibold text-red-600 dark:text-red-400">403 - {{ gp247_language_render('admin.deny_content') }}</h2>
    @if ($url)
    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ gp247_language_render('admin.deny_msg') }}</p>
    <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
        <strong>URL:</strong> <code>{{ $url }}</code> - <strong>Method:</strong> <code>{{ $method }}</code>
    </p>
    @endif
</div>
