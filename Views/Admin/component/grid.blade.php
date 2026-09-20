{{--
    Shared admin list grid (TailAdmin).

    Ported from the v1 AdminLTE/pjax grid that root/vendor_list and
    root/payment_list each carried a copy of. pjax is gone in core 2.x, so
    "refresh" and pagination are plain full-page navigations, and the bulk delete
    posts through mvp.postAndNotify() instead of $.ajax + SweetAlert.

    Expects the same variables the v1 grid did: $listTh, $dataTr, $pagination,
    $resultItems, $urlDeleteItem, $removeList, $buttonRefresh, and the pluggable
    $menuLeft / $menuRight / $topMenuLeft / $topMenuRight / $blockBottom slots.
--}}
<div x-data="{
        selected: [],
        get allSelected() {
            return this.$refs.rows && this.selected.length > 0
                && this.selected.length === this.$refs.rows.querySelectorAll('[data-row-id]').length;
        },
        toggleAll() {
            const ids = [...this.$refs.rows.querySelectorAll('[data-row-id]')].map(el => el.dataset.rowId);
            this.selected = this.allSelected ? [] : ids;
        },
        deleteSelected() {
            if (!this.selected.length) { return; }
            if (!confirm(@js(gp247_language_render('action.delete_confirm')))) { return; }
            mvp.postAndNotify(
                @js($urlDeleteItem ?? ''),
                { ids: this.selected.join(',') },
                @js(gp247_language_render('action.delete_confirm_deleted_msg')),
                () => window.location.reload()
            );
        },
    }">

    <x-gp247::card>
        <x-slot:header>
            <div class="flex flex-wrap items-center gap-2">
                @if (!empty($removeList))
                    <x-gp247::button variant="secondary" size="sm" x-on:click="toggleAll()"
                        title="{{ gp247_language_render('action.select_all') }}">
                        <i class="far fa-square"></i>
                    </x-gp247::button>
                    <x-gp247::button variant="danger" size="sm" x-on:click="deleteSelected()"
                        x-bind:disabled="selected.length === 0"
                        title="{{ gp247_language_render('action.delete') }}">
                        <i class="fas fa-trash-alt"></i>
                    </x-gp247::button>
                @endif

                @if (!empty($buttonRefresh))
                    <x-gp247::button variant="primary" size="sm" x-on:click="window.location.reload()"
                        title="{{ gp247_language_render('action.refresh') }}">
                        <i class="fas fa-sync-alt"></i>
                    </x-gp247::button>
                @endif

                @include($templatePathAdminVendor.'component.menu_block', ['items' => $menuLeft ?? []])
                @include($templatePathAdminVendor.'component.menu_block', ['items' => $topMenuLeft ?? []])
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @include($templatePathAdminVendor.'component.menu_block', ['items' => $topMenuRight ?? []])
                @include($templatePathAdminVendor.'component.menu_block', ['items' => $menuRight ?? []])
            </div>
        </x-slot:header>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        @if (!empty($removeList))
                        <th class="w-10 px-4 py-3"></th>
                        @endif
                        @foreach ($listTh as $key => $th)
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{!! $th !!}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50" x-ref="rows">
                    @forelse ($dataTr as $keyRow => $tr)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30" data-row-id="{{ $keyRow }}">
                        @if (!empty($removeList))
                        <td class="px-4 py-3">
                            <x-gp247::checkbox x-model="selected" value="{{ $keyRow }}" />
                        </td>
                        @endif
                        @foreach ($tr as $key => $trtd)
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{!! $trtd !!}</td>
                        @endforeach
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ count($listTh) + (empty($removeList) ? 0 : 1) }}" class="px-4 py-12 text-center">
                            <i class="fas fa-box-open mb-3 block text-3xl text-gray-300 dark:text-gray-600"></i>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.no_data') }}</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <span class="text-xs text-gray-500 dark:text-gray-400">{!! $resultItems ?? '' !!}</span>
                <div>{!! $pagination ?? '' !!}</div>
            </div>
        </x-slot:footer>
    </x-gp247::card>

    @include($templatePathAdminVendor.'component.menu_block', ['items' => $blockBottom ?? []])
</div>

@push('scripts')
<script>
    // Single-row delete, called from the per-row action links the list
    // controllers render as HTML strings. Kept as a global (rather than Alpine)
    // because those strings carry inline onclick handlers — the same contract as
    // the v1 grid, whose global deleteItem() used $.ajax + SweetAlert.
    window.deleteItem = function (id) {
        if (!confirm(@js(gp247_language_render('action.delete_confirm')) + ' #' + id)) {
            return;
        }
        mvp.postAndNotify(
            @js($urlDeleteItem ?? ''),
            { id: id, ids: id },
            @js(gp247_language_render('action.delete_confirm_deleted_msg')),
            () => window.location.reload()
        );
    };
</script>
@endpush
