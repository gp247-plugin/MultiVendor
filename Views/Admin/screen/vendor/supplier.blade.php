@extends($templatePathAdminVendor.'layout')

@section('main')

@php
    $id = empty($id) ? 0 : $id;
@endphp

{{-- Two-panel screen: edit form on the left, list on the right. --}}
<div class="grid grid-cols-1 gap-5 lg:grid-cols-2">

    <form action="{{ $url_action }}" method="post" accept-charset="UTF-8" id="form-main">
        @csrf

        <x-gp247::card>
            <x-slot:header>
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">{!! $title_action !!}</h3>
                </div>
                @if ($layout == 'edit')
                    <x-gp247::button variant="secondary" size="sm"
                        href="{{ gp247_route_admin('vendor_admin_supplier.index') }}"
                        title="{{ gp247_language_render('admin.back_list') }}">
                        <i class="fas fa-list"></i>
                        {{ gp247_language_render('admin.back_list') }}
                    </x-gp247::button>
                @endif
            </x-slot:header>

            <div class="space-y-4">
                <x-gp247::input name="name" id="name"
                    :label="gp247_language_render('admin.supplier.name')"
                    :value="old() ? old('name') : ($supplier['name'] ?? '')"
                    :error="$errors->first('name')" />

                <x-gp247::input name="alias" id="alias"
                    :label="strip_tags(gp247_language_render('admin.supplier.alias'))"
                    :value="old() ? old('alias') : ($supplier['alias'] ?? '')"
                    :error="$errors->first('alias')" />

                <x-gp247::input name="phone" id="phone"
                    :label="strip_tags(gp247_language_render('admin.supplier.phone'))"
                    :value="old() ? old('phone') : ($supplier['phone'] ?? '')"
                    :error="$errors->first('phone')" />

                <x-gp247::input name="url" id="url"
                    :label="strip_tags(gp247_language_render('admin.supplier.url'))"
                    :value="old() ? old('url') : ($supplier['url'] ?? '')"
                    :error="$errors->first('url')" />

                <x-gp247::input name="email" id="email" type="email"
                    :label="strip_tags(gp247_language_render('admin.supplier.email'))"
                    :value="old() ? old('email') : ($supplier['email'] ?? '')"
                    :error="$errors->first('email')" />

                <x-gp247::input name="address" id="address"
                    :label="strip_tags(gp247_language_render('admin.supplier.address'))"
                    :value="old() ? old('address') : ($supplier['address'] ?? '')"
                    :error="$errors->first('address')" />

                @include($templatePathAdminVendor.'component.media_input', [
                    'name' => 'image',
                    'type' => 'vendor_supplier',
                    'label' => strip_tags(gp247_language_render('admin.supplier.image')),
                    'value' => old() ? old('image') : ($supplier['image'] ?? ''),
                    'error' => $errors->first('image'),
                ])

                <x-gp247::input name="sort" id="sort" type="number" min="0"
                    :label="strip_tags(gp247_language_render('admin.supplier.sort'))"
                    :value="old() ? old('sort') : ($supplier['sort'] ?? 0)"
                    :error="$errors->first('sort')" />

                @include($templatePathAdminVendor.'component.custom_fields', [
                    'type' => 'shop_supplier',
                    'object' => $supplier ?: null,
                ])
            </div>

            <x-slot:footer>
                <div class="flex items-center justify-between">
                    <x-gp247::button type="reset" variant="warning">{{ gp247_language_render('action.reset') }}</x-gp247::button>
                    <x-gp247::button type="submit" variant="primary">{{ gp247_language_render('action.submit') }}</x-gp247::button>
                </div>
            </x-slot:footer>
        </x-gp247::card>
    </form>

    <x-gp247::card>
        <x-slot:header>
            <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                    <i class="fas fa-th-list text-gray-400"></i> {!! $title ?? '' !!}
                </h3>
            </div>
        </x-slot:header>

        <div class="overflow-x-auto" data-grid>
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
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @forelse ($dataTr as $keyRow => $tr)
                    <tr class="{{ request('id') == $keyRow ? 'bg-blue-50 dark:bg-blue-900/30' : 'hover:bg-gray-50 dark:hover:bg-gray-700/30' }}">
                        @if (!empty($removeList))
                        <td class="px-4 py-3">
                            <x-gp247::checkbox data-id="{{ $keyRow }}" />
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

</div>

@endsection

@push('scripts')
<script>
    // Single-row delete, called from the per-row action links the controller
    // renders as HTML strings (same contract as the shared grid).
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
