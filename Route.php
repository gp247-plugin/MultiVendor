<?php

/**
 * Root-admin screens — Livewire (Pha 1, ADR multi-vendor_admin-livewire-migration).
 *
 * Registered WITHOUT a 'namespace' group on purpose: a namespace prefix would
 * corrupt the ::class action strings (same reason RootConfigForm is registered
 * separately below). Create/edit/delete/process are Livewire actions inside the
 * components, so only the entry routes remain.
 */
Route::group(
    [
        'prefix' => GP247_ADMIN_PREFIX.'/MultiVendor',
        'middleware' => GP247_ADMIN_MIDDLEWARE,
    ],
    function () {
        // Store: list / create / config
        Route::get('/store', \App\GP247\Plugins\MultiVendor\Admin\Livewire\VendorStoreList::class)
            ->name('admin_MultiVendor.index');
        Route::get('/store/create', \App\GP247\Plugins\MultiVendor\Admin\Livewire\VendorStoreCreateForm::class)
            ->name('admin_MultiVendor.create');
        Route::get('/store/config/{id}', \App\GP247\Plugins\MultiVendor\Admin\Livewire\VendorStoreConfigForm::class)
            ->name('admin_MultiVendor.config');

        // Vendor user: ResourcePanel (list + inline create/edit)
        Route::get('/vendor', \App\GP247\Plugins\MultiVendor\Admin\Livewire\VendorUserManager::class)
            ->name('admin_MultiVendorUser.index');
        Route::get('/vendor/edit/{id}', \App\GP247\Plugins\MultiVendor\Admin\Livewire\VendorUserManager::class)
            ->name('admin_MultiVendorUser.edit');

        // Payment: list + process + edit (one component, mount receives {id})
        Route::get('/payment', \App\GP247\Plugins\MultiVendor\Admin\Livewire\VendorPaymentManager::class)
            ->name('admin_MultiVendorPayment.index');
        Route::get('/payment/edit/{id}', \App\GP247\Plugins\MultiVendor\Admin\Livewire\VendorPaymentManager::class)
            ->name('admin_MultiVendorPayment.edit');

        // Pro-feature gateway. Registered in BOTH editions on purpose: the menu
        // rows point here, so a Free marketplace meets an explanation instead of
        // a dead link, and installing Pro needs no menu change (the gateway
        // redirects). US-multi-vendor-pro-upgrade-funnel.
        Route::get('/pro/{feature}', \App\GP247\Plugins\MultiVendor\Admin\Livewire\ProGateway::class)
            ->name('admin_MultiVendor.pro');

    }
);

/**
 * Marketplace settings (Livewire).
 *
 * Registered outside the controller group above on purpose: that group sets a
 * `namespace`, which Laravel would prefix onto a class-string action.
 */
Route::group(
    [
        'prefix' => GP247_ADMIN_PREFIX.'/MultiVendor/config',
        'middleware' => GP247_ADMIN_MIDDLEWARE,
    ],
    function () {
        Route::get('/', \App\GP247\Plugins\MultiVendor\Livewire\RootConfigForm::class)
            ->name('admin_MultiVendorConfig.index');
    }
);

if (gp247_config_global('MultiVendor')) {
    /**
     * vendor manager (single-domain marketplace)
     */

    Route::group(
        [
            'prefix' => config('Plugins/MultiVendor.route.MULTIVENDOR_ADMIN_PATH'),
            'middleware' => ['web', 'vendor'],
            'namespace' => 'App\GP247\Plugins\MultiVendor\Admin\Controllers',
        ], 
        function () {

            if (gp247_config_global('MultiVendor_allow_register')) {
                Route::get('register', 'Auth\RegisterController@showRegister')->name('vendor.register');
                Route::post('register', 'Auth\RegisterController@postRegister')->name('vendor.postRegister');
            }
            Route::get('forgot', 'Auth\ForgotPasswordController@getForgot')->name('vendor.forgot');
            Route::post('forgot', 'Auth\ForgotPasswordController@sendRepostForgotsetLinkEmail')->name('vendor.postForgot');
            Route::get('password/reset/{token}', 'Auth\ResetPasswordController@formResetPassword')->name('vendor.password_reset');
            Route::post('password/reset', 'Auth\ResetPasswordController@reset')->name('vendor.password_request');
            Route::get('setting', 'Auth\LoginController@getSetting')->name('vendor.setting');
            Route::post('setting', 'Auth\LoginController@putSetting')->name('vendor.postSetting');
            Route::get('login', 'Auth\LoginController@getLogin')->name('vendor.login');
            Route::post('login', 'Auth\LoginController@postLogin')->name('vendor.postLogin');
            Route::get('logout', 'Auth\LoginController@getLogout')->name('vendor.logout');
        }
    );
    
    Route::group(
        [
            'prefix' => config('Plugins/MultiVendor.route.MULTIVENDOR_ADMIN_PATH'),
            'middleware' => ['web', 'vendor'],
            'namespace' => 'App\GP247\Plugins\MultiVendor\Admin\Controllers\Vendor',
        ], 
        function () {

            //Language
            Route::get('locale/{code}', function ($code) {
                session(['locale' => $code]);
                return back();
            })->name('vendor_admin.locale');

            Route::get('deny', 'DashboardVendorController@deny')->name('vendor_admin.deny');
            Route::get('data_not_found', 'DashboardVendorController@dataNotFound')->name('vendor_admin.data_not_found');
            Route::get('deny_single', 'DashboardVendorController@denySingle')->name('vendor_admin.deny_single');
            Route::get('account_inactive', 'DashboardVendorController@accountInactive')->name('vendor_admin.account_inactive');


            Route::group(['middleware' => ['checkVendorActive', 'checkStoreExist']], function () {

                // Dashboard — Livewire (leading-backslash FQN so this namespaced
                // group does not prefix the controller namespace onto it).
                Route::get('/', '\App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor\VendorDashboard')->name('vendor_admin.home');

                if (starts_with(request()->path(), config('Plugins/MultiVendor.route.MULTIVENDOR_ADMIN_PATH'))) {
                    
                    Route::group(['prefix' => 'uploads'], function (){
                        Route::get('/', 'VendorUploadController@show')->name('vendor.lfm.show');
                        Route::get('/errors', 'VendorUploadController@getErrors')->name('vendor.lfm.getErrors');
                    });

                    Route::group(['prefix' => 'uploads', 'namespace' => '\\UniSharp\\LaravelFilemanager\\Controllers\\'], function () {
                        // upload

                    // upload
                    Route::post('/upload', [
                        'uses' => 'UploadController@upload',
                        'as' => 'vendor.lfm.upload',
                    ]);
                
                    // list images & files
                    Route::get('/jsonitems', [
                        'uses' => 'ItemsController@getItems',
                        'as' => 'vendor.lfm.getItems',
                    ]);
                
                    Route::get('/move', [
                        'uses' => 'ItemsController@move',
                        'as' => 'vendor.lfm.move',
                    ]);
                
                    Route::get('/domove', [
                        'uses' => 'ItemsController@domove',
                        'as' => 'vendor.lfm.domove',
                    ]);
                
                    // folders
                    Route::get('/newfolder', [
                        'uses' => 'FolderController@getAddfolder',
                        'as' => 'vendor.lfm.getAddfolder',
                    ]);
                
                    // list folders
                    Route::get('/folders', [
                        'uses' => 'FolderController@getFolders',
                        'as' => 'vendor.lfm.getFolders',
                    ]);
                
                    // crop
                    Route::get('/crop', [
                        'uses' => 'CropController@getCrop',
                        'as' => 'vendor.lfm.getCrop',
                    ]);
                    Route::get('/cropimage', [
                        'uses' => 'CropController@getCropimage',
                        'as' => 'vendor.lfm.getCropimage',
                    ]);
                    Route::get('/cropnewimage', [
                        'uses' => 'CropController@getNewCropimage',
                        'as' => 'vendor.lfm.getCropnewimage',
                    ]);
                
                    // rename
                    Route::get('/rename', [
                        'uses' => 'RenameController@getRename',
                        'as' => 'vendor.lfm.getRename',
                    ]);
                
                    // scale/resize
                    Route::get('/resize', [
                        'uses' => 'ResizeController@getResize',
                        'as' => 'vendor.lfm.getResize',
                    ]);
                    Route::get('/doresize', [
                        'uses' => 'ResizeController@performResize',
                        'as' => 'vendor.lfm.performResize',
                    ]);
                
                    // download
                    Route::get('/download', [
                        'uses' => 'DownloadController@getDownload',
                        'as' => 'vendor.lfm.getDownload',
                    ]);
                
                    // delete
                    Route::get('/delete', [
                        'uses' => 'DeleteController@getDelete',
                        'as' => 'vendor.lfm.getDelete',
                    ]);
                    });
                }

                Route::group(['prefix' => '/order'], function () {
                        // List is Livewire (leading-backslash FQN so this namespaced
                        // group does not prefix the controller namespace onto it).
                        // Detail/update stay on the controller (that screen is Pha 2b).
                        Route::get('/', '\App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor\VendorOrderList')->name('vendor_admin_order.index');
                        // Detail is Livewire too; {id} flows to the component mount().
                        // The save is now the updateStatus() action, so the old POST
                        // /update route is retired.
                        Route::get('/detail/{id}', '\App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor\VendorOrderDetail')->name('vendor_admin_order.detail');
                        // S1-3: printable packing slip (core invoice view, store-fenced in the controller).
                        Route::get('/print/{id}', 'VendorOrderController@print')->name('vendor_admin_order.print');
                    }
                );
                // Category — Livewire (list + inline create/edit/delete). The
                // leading-backslash FQN string prevents this namespaced group from
                // prefixing the controller namespace onto the component class.
                // WHY '/category-mng' not '/category': a front SEO route shadows the
                // literal `vendor_admin/category` path (the legacy controller screen
                // 404'd there too). The route NAME is unchanged so the sidebar link
                // follows. The leading-backslash FQN keeps this namespaced group from
                // prefixing the controller namespace onto the Livewire component.
                Route::get('/category-mng', '\App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor\VendorCategoryManager')
                    ->name('vendor_admin_category.index');

                // Product — ONE Livewire two-panel screen inheriting the core
                // ProductManager (form left + list right, Kind buttons, tabs). It
                // replaces the old separate list + builder: create/edit happen in
                // place (kind via the form's Kind buttons; edit via the inherited
                // ?edit deep-link), so the old create/build_create/group_create/edit
                // routes and the delete POST are gone (delete is an in-component
                // action). WHY '/product-mng' not '/product': the storefront
                // product.all route shadows the bare path; the route NAME is kept so
                // the sidebar link follows.
                Route::get('/product-mng', '\App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor\VendorProductManager')
                    ->name('vendor_admin_product.index');

                // Import is not yet migrated — it stays on the controller.
                Route::group(['prefix' => 'product'], function () {
                    Route::get('/import', 'VendorProductController@import')->name('vendor_admin_product.import');
                    Route::post('/import', 'VendorProductController@postImport')->name('vendor_admin_product.import');
                });

                // Banner/Supplier — single Livewire screen each (list + inline
                // create/edit/delete as component actions). Leading-backslash FQN
                // keeps the namespaced group from prefixing the controller namespace.
                Route::get('/banner', '\App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor\VendorBannerManager')
                    ->name('vendor_admin_banner.index');

                Route::get('/supplier', '\App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor\VendorSupplierManager')
                    ->name('vendor_admin_supplier.index');

                // Payment — read-only Livewire list.
                Route::get('/payment', '\App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor\VendorPaymentList')
                    ->name('vendor_admin_payment.index');

                // Vendor store info — Livewire form (save is a component action; the
                // old POST /update_info route is retired with the controller screen).
                Route::group(
                    [
                        'prefix' => 'vendor_info',
                    ],
                    function () {
                        Route::get('/', '\App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor\VendorStoreInfoForm')
                            ->name('vendor_admin_store.index');
                    }
                );

                // S3-2 (Pro): identity profile of the store (text KYC) — status + submit/resubmit.
                // Pro-feature gateway for the vendor area (always registered —
                // the sidebar shows locked Pro items that point here).
                Route::get('/pro/{feature}', '\App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor\VendorProGateway')
                    ->name('vendor_admin.pro');

                // S2-1 (Pro): plugins the marketplace opened to vendors — per-store on/off
                // list + a generic parameter form delegating to the plugin's own ConfigForm.
            });
        }
    );


//Front-end

    // Multi vendor only active for vendor root
    if(config('app.storeId') == GP247_STORE_ID_ROOT) {
        /**
         * Route shop
         */
        $langUrl = GP247_SEO_LANG ?'{lang?}/' : '';
        Route::group(
            [
                'prefix' => $langUrl.config('Plugins/MultiVendor.route.MULTIVENDOR_FRONT_PATH'),
                'middleware' => GP247_FRONT_MIDDLEWARE,
                'namespace' => 'App\GP247\Plugins\MultiVendor\Controllers',
            ], 
            function () {
                // S2-5: the prefix itself is the marketplace directory; a store page needs its code.
                Route::get('/', 'FrontController@vendorIndex')->name('MultiVendor.index');
                Route::get('/{code}', 'FrontController@vendorDetail')->name('MultiVendor.detail');
                // S3-3 (Pro): a signed-in customer opens / withdraws a dispute on their own order
                // from the order page (storefront extension point shop_order_detail_bottom).
                if (\App\GP247\Plugins\MultiVendor\Tier\Tier::allows(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_DISPUTE)) {
                    Route::post('/dispute/{order}', 'FrontController@openDispute')->middleware(['customer', 'throttle:10,1'])->name('MultiVendor.dispute_open');
                    Route::post('/dispute/{order}/withdraw', 'FrontController@withdrawDispute')->middleware(['customer', 'throttle:10,1'])->name('MultiVendor.dispute_withdraw');
                }
            }
        );
    }
    $prefixCategoryvendor = config('Plugins/MultiVendor.route.PREFIX_CATEGORY_VENDOR');
    Route::group(
        [
            'prefix' => $prefixCategoryvendor,
            'middleware' => GP247_FRONT_MIDDLEWARE,
            'namespace' => 'App\GP247\Plugins\MultiVendor\Controllers',
        ], 
        function () {
            Route::get('/{alias}/{storeId}.html', 'FrontController@categoryVendorDetail')->name('MultiVendor_category.detail');
        }
    );

}


    
