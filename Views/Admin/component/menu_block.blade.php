{{--
    Renders one of the pluggable menu slots a list screen exposes
    ($menuLeft / $menuRight / $topMenuLeft / $topMenuRight / $blockBottom).

    Each entry is either a raw HTML snippet or a "view::<view.name>" reference —
    the same contract the v1 grid used, kept intact so third-party code that
    injects into these slots via gp247_config_group() keeps working.

    @param array $items
--}}
@foreach ($items as $item)
    @php
        $arrCheck = explode('view::', $item);
    @endphp
    @if (count($arrCheck) == 2)
        @if (view()->exists($arrCheck[1]))
            @include($arrCheck[1])
        @endif
    @else
        {!! trim($item) !!}
    @endif
@endforeach
