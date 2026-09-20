{{--
    MultiVendor paginator — TailAdmin styling.

    Replaces the v1 Bootstrap `.pagination > .page-item > .page-link` markup, whose
    styles came from AdminLTE (gone in core 2.x). The `pjax-container` hooks are
    dropped too: page links are plain full-page navigations now.
--}}
@if ($paginator->hasPages())
<nav class="flex items-center gap-1" role="navigation" aria-label="{{ gp247_language_render('admin.pagination') ?: 'Pagination' }}">

    @php
        $base = 'inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm transition';
        $idle = $base.' border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600';
        $active = $base.' border border-blue-600 bg-blue-600 font-semibold text-white';
        $disabled = $base.' border border-gray-200 bg-gray-50 text-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-600';
    @endphp

    {{-- Previous --}}
    @if ($paginator->onFirstPage())
        <span class="{{ $disabled }}">&laquo;</span>
    @else
        <a class="{{ $idle }}" href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo;</a>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="{{ $disabled }}">{{ $element }}</span>
        @endif

        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="{{ $active }}">{{ $page }}</span>
                @else
                    <a class="{{ $idle }}" href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    {{-- Next --}}
    @if ($paginator->hasMorePages())
        <a class="{{ $idle }}" href="{{ $paginator->nextPageUrl() }}" rel="next">&raquo;</a>
    @else
        <span class="{{ $disabled }}">&raquo;</span>
    @endif
</nav>
@endif
