{{--
    Vendor user manager (MultiVendor root-admin) — two-panel: add/edit form
    (left) + live list (right) on the core ResourcePanel base (ADR-005,
    ui-tailadmin P1). Livewire port of the legacy vendor_user_add.blade.php +
    vendor_list.blade.php. UI text via gp247_language_render; no jQuery.

    @aidlc-unit multi-vendor-pro
    @aidlc-story US-multi-vendor-pro-root-admin-livewire
    @aidlc-adr multi-vendor_admin-livewire-migration

    Variables: $rows (AdminVendorUser paginator), $countryOptions, $storeOptions,
               $form, $editingId, $sortField, $sortDir, $keyword.
--}}
@php($storeCodes = gp247_store_get_list_code())
<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    {{-- Left: add / edit form --}}
    <x-gp247::card :title="gp247_language_render($editingId ? 'action.edit' : 'multi_vendor.vendor_add')">
        <form wire:submit="save" class="space-y-4">

            @if ($errors->any())
                <div class="rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-700 dark:border-red-500 dark:bg-red-900/30 dark:text-red-200">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-gp247::input name="first_name"
                :label="gp247_language_render('multi_vendor.first_name')"
                wire:model="form.first_name"
                :error="$errors->first('form.first_name')"
                data-testid="multi-vendor-pro-user-first-name"
                required />

            <x-gp247::input name="last_name"
                :label="gp247_language_render('multi_vendor.last_name')"
                wire:model="form.last_name"
                :error="$errors->first('form.last_name')"
                required />

            <x-gp247::input name="phone"
                :label="gp247_language_render('multi_vendor.phone')"
                wire:model="form.phone"
                :error="$errors->first('form.phone')" />

            <x-gp247::input name="postcode"
                :label="gp247_language_render('multi_vendor.postcode')"
                wire:model="form.postcode"
                :error="$errors->first('form.postcode')" />

            <x-gp247::input type="email" name="email"
                :label="gp247_language_render('multi_vendor.email')"
                wire:model="form.email"
                :error="$errors->first('form.email')"
                data-testid="multi-vendor-pro-user-email"
                required />

            <x-gp247::input name="address1"
                :label="gp247_language_render('multi_vendor.address1')"
                wire:model="form.address1"
                :error="$errors->first('form.address1')" />

            <x-gp247::input name="address2"
                :label="gp247_language_render('multi_vendor.address2')"
                wire:model="form.address2"
                :error="$errors->first('form.address2')" />

            <x-gp247::searchable-select
                model="form.country"
                :label="gp247_language_render('multi_vendor.country')"
                :options="$countryOptions"
                :error="$errors->first('form.country')" />

            {{-- Blank on edit keeps the current password (server-side parity). --}}
            <x-gp247::input type="text" name="password"
                :label="gp247_language_render('multi_vendor.password')"
                wire:model="form.password"
                :error="$errors->first('form.password')"
                :help="$editingId ? gp247_language_render('multi_vendor.admin.keep_password') : null" />

            {{-- Vendor's own store (ROOT store id 1 excluded in the component). --}}
            <x-gp247::searchable-select
                model="form.store_id"
                :label="gp247_language_render('admin.select_store')"
                :placeholder="gp247_language_render('admin.select_store')"
                :options="$storeOptions"
                :error="$errors->first('form.store_id')"
                data-testid="multi-vendor-pro-user-store"
                required />

            <x-gp247::checkbox name="status" value="1"
                :label="gp247_language_render('multi_vendor.status')"
                wire:model="form.status" />

            <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
                <x-gp247::button variant="secondary" wire:click="cancelEdit" type="button"
                    data-testid="multi-vendor-pro-user-cancel">
                    {{ gp247_language_render($editingId ? 'admin.cancel' : 'action.reset') }}
                </x-gp247::button>
                <x-gp247::button type="submit" variant="primary" wire:loading.attr="disabled"
                    data-testid="multi-vendor-pro-user-submit">
                    <i class="fas fa-save"></i>
                    {{ gp247_language_render($editingId ? 'admin.update' : 'action.submit') }}
                </x-gp247::button>
            </div>
        </form>
    </x-gp247::card>

    {{-- Right: list --}}
    <x-gp247::card :title="gp247_language_render('multi_vendor.vendor_user')">
        <div class="mb-3">
            <input type="search" wire:model.live.debounce.300ms="keyword"
                placeholder="{{ gp247_language_render('multi_vendor.vendor_search_place') }}"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
        </div>

        <x-gp247::table :empty="$rows->isEmpty() ? gp247_language_render('admin.no_records') : null">
            <x-slot:head>
                <tr>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('first_name')">
                        {{ gp247_language_render('multi_vendor.name') }} @if ($sortField === 'first_name')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('email')">
                        {{ gp247_language_render('multi_vendor.email') }} @if ($sortField === 'email')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('front.store_list') }}</th>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('status')">
                        {{ gp247_language_render('multi_vendor.status') }} @if ($sortField === 'status')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('created_at')">
                        {{ gp247_language_render('admin.created_at') }} @if ($sortField === 'created_at')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('action.title') }}</th>
                </tr>
            </x-slot:head>

            @foreach ($rows as $row)
                @php($codeStore = $storeCodes[$row->store_id] ?? '')
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ (string) $row->id === (string) $editingId ? 'bg-blue-100 border-l-4 border-blue-500 dark:bg-blue-900 dark:border-blue-500' : '' }}"
                    wire:key="vendor-user-{{ $row->id }}"
                    data-testid="multi-vendor-pro-user-row">
                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $row->name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row->email }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                        @if ($codeStore)
                            <i class="fab fa-shopify text-gray-500 dark:text-gray-400"></i>
                            <a href="{{ gp247_route_front('MultiVendor.detail', ['code' => $codeStore]) }}"
                                target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">{{ $codeStore }}</a>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <x-gp247::badge :color="$row->status ? 'green' : 'gray'">{{ $row->status ? gp247_language_render('admin.active') : gp247_language_render('admin.inactive') }}</x-gp247::badge>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $row->created_at }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <x-gp247::button size="sm" variant="ghost" wire:click="editRow('{{ $row->id }}')" data-testid="multi-vendor-pro-user-edit"><i class="fas fa-edit"></i></x-gp247::button>
                            <x-gp247::button size="sm" variant="ghost" wire:click="delete('{{ $row->id }}')" wire:confirm="{{ gp247_language_render('action.delete_confirm') }}"><i class="fas fa-trash-alt text-red-600"></i></x-gp247::button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-gp247::table>

        <div class="mt-4">{{ $rows->links('gp247-admin::partials.pagination') }}</div>
    </x-gp247::card>
</div>
