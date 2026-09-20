<?php
    $config = file_get_contents(__DIR__.'/gp247.json');
    $config = json_decode($config, true);
    $extensionPath = $config['configGroup'].'/'.$config['configKey'];

    $this->loadTranslationsFrom(__DIR__.'/Lang', $extensionPath);
    $this->loadViewsFrom(__DIR__.'/Views', $extensionPath);

    if (gp247_extension_check_active($config['configGroup'], $config['configKey'])) {

        $this->commands([
            \App\GP247\Plugins\MultiVendor\Commands\SampleData::class,
        ]);

        config(['auth.guards.vendor.driver' => 'session']);
        config(['auth.guards.vendor.provider' => 'vendors']);
        config(['auth.providers.vendors.driver' => 'eloquent']);
        config(['auth.providers.vendors.model' => 'App\GP247\Plugins\MultiVendor\Models\VendorUser']);
        config(['auth.passwords.vendors.provider' => 'vendors']);
        config(['auth.passwords.vendors.table' => 'vendor_password_resets']);
        config(['auth.passwords.vendors.expire' => '60']);


        app('router')->aliasMiddleware('checkStoreExist', \App\GP247\Plugins\MultiVendor\Middleware\CheckStoreExist::class);
        app('router')->aliasMiddleware('checkVendorActive', \App\GP247\Plugins\MultiVendor\Middleware\CheckVendorActive::class);
        app('router')->aliasMiddleware('vendor.auth', \App\GP247\Plugins\MultiVendor\Middleware\Authenticate::class);
        app('router')->aliasMiddleware('vendor.storeId', \App\GP247\Plugins\MultiVendor\Middleware\AdminStoreId::class);
        app('router')->middlewareGroup('vendor', ['vendor.auth', 'vendor.storeId', 'localization']);

        //Load helper
        foreach (glob(__DIR__.'/Helpers/*.php') as $filename) {
            require_once $filename;
        }
   
        if (file_exists(__DIR__.'/config.php')) {
            $this->mergeConfigFrom(__DIR__.'/config.php', $extensionPath);
        }

        // WHY: store the i18n KEY, not the raw token — the "Layout block" admin
        // screen renders each option through gp247_language_render(), so a raw
        // token showed up untranslated as "vendor_home - vendor_home".
        // Keys must stay equal to the $layout_page value FrontController emits.
        $configLayoutPage = config('gp247-config.front.layout_page', []);
        $configLayoutPage['vendor_home'] = $extensionPath.'::lang.layout_block_page.vendor_home';
        $configLayoutPage['vendor_product_list'] = $extensionPath.'::lang.layout_block_page.vendor_product_list';
        $configLayoutPage['vendor_index'] = $extensionPath.'::lang.layout_block_page.vendor_index';
        config(['gp247-config.front.layout_page' => $configLayoutPage]);

        // S3-3: dispute box under the customer's order page through the storefront
        // extension point (ADR front_storefront-plugin-hooks) — no template edit
        // needed once the screen calls gp247_render_plugin_hook('shop_order_detail_bottom').
        $hooks = config('gp247-config.front.plugin_hooks', []);
        $hooks['shop_order_detail_bottom'][] = [
            'key' => 'MultiVendor',
            'callback' => [\App\GP247\Plugins\MultiVendor\Hooks\OrderDetailHook::class, 'render'],
        ];
        config(['gp247-config.front.plugin_hooks' => $hooks]);


        //Config for file manager
        $configLfm = config('lfm.folder_categories');
        $formatConfig = [
            'folder_name' => 'format_folder_name',
            'startup_view' => 'grid',
            'max_size' => 30000, // size in KB
            'valid_mime' => [
                'image/jpeg',
                'image/pjpeg',
                'image/png',
                'image/gif',
                'image/svg+xml',
                'image/webp'
            ],
        ];
        // WHY 'category_store' is in this list: the vendor category screen's file
        // picker asks for the 'vendor_category_store' category. It was never
        // registered, so the picker had no folder config to work with.
        foreach (['product', 'category', 'category_store', 'supplier', 'customer','logo','avatar','banner','page'] as $item) {
            $formatConfigTmp = $formatConfig;
            $formatConfigTmp['folder_name'] = $item;
            $configLfm['vendor_'.$item] = $formatConfigTmp;
        }
        config(['lfm.folder_categories' => $configLfm]);



        // Marketplace e-mails (S1-2, US-multi-vendor-pro-marketplace-notifications).
        //
        // WHY a model event + DB::afterCommit instead of a core hook: gp247/shop has
        // no order event to listen to and the "new order" mail must not touch core
        // (NFR-mv-no-core-front-change). ShopOrder::created fires for every source
        // (checkout AND admin-created orders); afterCommit defers the mail until the
        // order lines exist — checkout inserts the details after the order row, in
        // the same transaction. Outside a transaction the callback runs immediately.
        \GP247\Shop\Models\ShopOrder::created(function ($order) {
            \Illuminate\Support\Facades\DB::afterCommit(function () use ($order) {
                \App\GP247\Plugins\MultiVendor\Notifications\VendorNotifier::orderCreated($order);
            });
        });
        // Vendor self-registration lands here (RegisterController dispatches
        // CreatedVendorUser); the notifier itself skips stores that are already open.
        \Illuminate\Support\Facades\Event::listen(
            \App\GP247\Plugins\MultiVendor\Events\CreatedVendorUser::class,
            function ($event) {
                \App\GP247\Plugins\MultiVendor\Notifications\VendorNotifier::vendorRegistered($event->data);
            }
        );

        //Path view admin
        view()->share('templatePathAdminVendor', $extensionPath.'::Admin.');
    }