{{--
    Vendor store list (v2 Livewire port of the legacy store_list view): a create
    button, then one row per store with the marketplace shop link, an inline
    open/close (`status`) toggle for every sub-store, and Configure / Delete
    actions. No domain column and no per-store website link (single-domain
    marketplace); the root store is neither toggled nor deleted. All text via
    gp247_language_render.

    @aidlc-unit multi-vendor-pro
    @aidlc-story US-multi-vendor-pro-root-admin-livewire
    @aidlc-adr multi-vendor_admin-livewire-migration

    Variables: $stories (AdminStore collection keyed by id), $pathPlugin.
--}}
<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">
            {{ gp247_language_render('admin.store.list') }}
        </h3>
        <x-gp247::button href="{{ gp247_route_admin('admin_MultiVendor.create') }}" variant="primary" size="sm"
            data-testid="multi-vendor-pro-store-create">
            <i class="fa fa-plus"></i> {{ gp247_language_render('admin.store.add_new') }}
        </x-gp247::button>
    </div>

    <x-gp247::table :empty="$stories->isEmpty() ? gp247_language_render('admin.display.data_not_found') : null">
        <x-slot:head>
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.store.title') }}</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render($pathPlugin.'::lang.admin.store_url') }}</th>
                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render($pathPlugin.'::lang.admin.store_open') }}</th>
                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('multi_vendor.commission_column') }}</th>
                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.actions') }}</th>
            </tr>
        </x-slot:head>

        @foreach ($stories as $key => $store)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50" wire:key="vendor-store-{{ $store->id }}"
                data-testid="multi-vendor-pro-store-row">
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                    <i class="fas fa-home mr-1.5 text-gray-400"></i>{{ $store->getTitle() }}
                    @if (!empty($verified[$store->id]))
                        <span class="ml-1 inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200" title="{{ gp247_language_render('multi_vendor.kyc.verified') }}" data-testid="multi-vendor-store-verified"><i class="fas fa-check-circle mr-1"></i>{{ gp247_language_render('multi_vendor.kyc.verified') }}</span>
                    @endif
                    @if (!empty($plans[$store->id]))
                        <span class="ml-1 inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-200" title="{{ gp247_language_render('multi_vendor.plan.plan') }}" data-testid="multi-vendor-store-plan"><i class="fas fa-layer-group mr-1"></i>{{ $plans[$store->id] }}</span>
                    @endif
                    <span class="text-xs text-gray-400">(#{{ $store->code }})</span>
                </td>
                <td class="px-4 py-3 text-sm">
                    <a href="{{ gp247_path_vendor($store->code) }}" target="_blank" rel="noopener"
                        class="inline-flex items-center gap-1.5 text-blue-600 hover:underline dark:text-blue-400"
                        title="{{ gp247_language_render($pathPlugin.'::lang.admin.store_shop') }}">
                        <i class="fab fa-shopify"></i>{{ $store->code }}
                    </a>
                </td>
                {{-- Marketplace open/close (`status`) toggle. Sub-stores only; the root
                     store is always reachable and shows no toggle (v1 rule). --}}
                <td class="px-4 py-3 text-center">
                    @if ($key != GP247_STORE_ID_ROOT)
                        <label class="inline-flex cursor-pointer items-center">
                            <input type="checkbox" wire:model.live="status.{{ $store->id }}" class="peer sr-only"
                                data-testid="multi-vendor-pro-store-status">
                            <span class="relative h-6 w-11 rounded-full bg-gray-300 transition-colors after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-all peer-checked:bg-blue-600 peer-checked:after:translate-x-5 dark:bg-gray-600"></span>
                        </label>
                    @endif
                </td>
                <td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-200" data-testid="multi-vendor-pro-store-commission-cell">
                    @if ($key != GP247_STORE_ID_ROOT)
                        {{ $commissions[$store->id]['rate'] ?? 0 }}%
                        @unless (!empty($commissions[$store->id]['overridden']))
                            <span class="text-xs text-gray-400">{{ gp247_language_render('multi_vendor.commission_marketplace') }}</span>
                        @endunless
                    @else
                        <span class="text-xs text-gray-400">—</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <x-gp247::row-actions
                        :delete-id="$key != GP247_STORE_ID_ROOT ? $store->id : null"
                        :locked="$key == GP247_STORE_ID_ROOT"
                        :delete-confirm="gp247_language_render('action.delete_confirm').' #'.$store->id">
                        <x-gp247::button size="sm" variant="ghost"
                            href="{{ gp247_route_admin('admin_MultiVendor.config', ['id' => $store->id]) }}"
                            title="{{ gp247_language_render($pathPlugin.'::lang.admin.store_config') }}">
                            <i class="fas fa-cogs"></i>
                        </x-gp247::button>
                    </x-gp247::row-actions>
                </td>
            </tr>
        @endforeach
    </x-gp247::table>

    <div class="space-y-1 border-t border-gray-200 pt-4 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
        <p><span class="text-red-600">*{{ gp247_language_render($pathPlugin.'::lang.admin.store_open') }}</span> : {{ gp247_language_render($pathPlugin.'::lang.admin.store_open_help') }}</p>
    </div>
</div>
