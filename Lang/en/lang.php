<?php
return [
    'title'      => 'Multiple Vendor',
    'parent_order' => 'Order parent',
    // Install-time mutual exclusion with MultiStore (S2-3) — file lang because DB rows are not seeded yet.
    'conflict_multi_store' => 'Cannot install: a multi-store plugin (MultiStore / MultiStorePro) is installed on this site. Multi-store and multi-vendor are different business models and cannot run together — uninstall the multi-store plugin first.',
    'admin'      => [
        'order_vendor'   => 'Order store',
        'title'          => 'Multiple Vendor',
        'help'           => '',
        'store_open'     => 'Open/Close',
        'store_open_help'=> 'The store opens/closes on the system, all activities of the stall are locked (including the store).',
        'store_config'   => 'config store',
        'store_remove'   => 'Remove store',
        'store_shop'     => 'Shop',
        'store_mode'     => 'Store mode',
        'store_url'      => 'Go to shop',
        'note_admin_login' => 'You are logged in with admin account.<br>Please logout admin',
    ],
    // Heading of the "Top new vendors" storefront block (template/blocks/vendor_new).
    'top_new_vendor' => 'Top new vendors',
    // Labels for the storefront page-types this plugin registers into
    // config('gp247-config.front.layout_page') (see Provider.php) so admins can
    // attach LayoutBlock blocks to the vendor pages.
    'layout_block_page' => [
        'vendor_home'         => 'Vendor home',
        'vendor_product_list' => 'Vendor product list',
        'vendor_index'        => 'Vendor directory',
    ],
];
