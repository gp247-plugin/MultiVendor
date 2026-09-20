<?php
return [
    'title'      => 'Chợ online',
    'parent_order' => 'Đơn hàng cha',
    // Install-time mutual exclusion with MultiStore (S2-3) — file lang because DB rows are not seeded yet.
    'conflict_multi_store' => 'Không thể cài đặt: website này đang cài plugin multi-store (MultiStore / MultiStorePro). Multi-store và multi-vendor là hai mô hình kinh doanh khác nhau, không thể chạy chung — hãy gỡ plugin multi-store trước.',
    'admin'      => [
        'order_vendor'   => 'Đơn hàng Shop',
        'title'          => 'Chợ online',
        'help'           => '',
        'store_open'     => 'Mở / Đóng',
        'store_open_help' => 'Gian hàng đóng/mở trên hệ thống, mọi hoạt động của gian hàng đều bị khóa',
        'store_config'   => 'Cấu hình cửa hàng',
        'store_remove'   => 'Gỡ bỏ cửa hàng',
        'store_shop'     => 'Gian hàng',
        'store_mode'     => 'Chế độ',
        'store_url'      => 'Tới gian hàng',
        'note_admin_login' => 'Bạn đang đăng nhập bằng tài khoản admin.<br>Vui lòng đăng xuất admin',
    ],
    // Tiêu đề block storefront "Nhà cung cấp mới nhất" (template/blocks/vendor_new).
    'top_new_vendor' => 'Nhà cung cấp mới nhất',
    // Nhãn cho các page-type storefront mà plugin đăng ký vào
    // config('gp247-config.front.layout_page') (xem Provider.php).
    'layout_block_page' => [
        'vendor_home'         => 'Trang chủ gian hàng',
        'vendor_product_list' => 'Danh sách sản phẩm gian hàng',
        'vendor_index'        => 'Danh bạ gian hàng',
    ],
];
