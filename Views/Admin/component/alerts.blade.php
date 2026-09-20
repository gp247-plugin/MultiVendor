{{--
    Session-flash → toast bridge for the vendor shell.

    v1 rendered a SweetAlert `Toast.fire(...)` per flash key. core 2.x ships no
    SweetAlert; the shell's own <x-gp247::toast> listens for a `notify` browser
    event instead, and core already provides a bridge for the standard keys —
    included here rather than duplicated.

    Only `message` and `status` need extra handling: they are this plugin's own
    legacy flash keys (both used for success messages) and are not in core's map.
--}}
@include('gp247-admin::partials.flash-toast')

@foreach (['message', 'status'] as $mvpFlashKey)
    @if (session($mvpFlashKey))
        {{-- @js() JSON-encodes the (i18n) message safely into the JS context. --}}
        <div
            data-testid="vendor-flash-toast-success"
            x-data
            x-init="$dispatch('notify', { type: 'success', message: @js(session($mvpFlashKey)) })"
            class="hidden"
            aria-hidden="true"
        ></div>
    @endif
@endforeach
