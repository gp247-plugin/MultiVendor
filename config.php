<?php
return [
    'route' => [
        /*
         * WHY 'shop' and not 'vendor': Laravel's public/.htaccess (and the usual
         * Nginx try_files) only forwards a request to index.php when the path
         * does NOT match a real file or directory. 'vendor' collides with
         * public/vendor/ — where packages such as laravel-filemanager publish
         * their assets — so /vendor was answered by the web server itself
         * (403 with -Indexes) and the directory route never ran. Any value set
         * here must not name a directory that exists under public/.
         * ADR-multi-vendor-storefront-path-default.
         */
        'MULTIVENDOR_FRONT_PATH' => env('MULTIVENDOR_FRONT_PATH', 'shop'),
        'MULTIVENDOR_ADMIN_PATH' => env('MULTIVENDOR_ADMIN_PATH', 'vendor_admin'),
        'PREFIX_QUICK_ORDER_VENDOR' => env('PREFIX_QUICK_ORDER_VENDOR', 'quick-order'),
        'PREFIX_CATEGORY_VENDOR' => env('PREFIX_CATEGORY_VENDOR', 'category-vendor'),
    ],
];