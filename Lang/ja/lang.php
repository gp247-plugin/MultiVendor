<?php
return [
    'title'      => 'マルチベンダー',
    'parent_order' => '親注文',
    // Install-time mutual exclusion with MultiStore (S2-3) — file lang because DB rows are not seeded yet.
    'conflict_multi_store' => 'インストールできません：このサイトにはマルチストアプラグイン（MultiStore / MultiStorePro）がインストールされています。マルチストアとマルチベンダーは異なるビジネスモデルのため併用できません。先にマルチストアプラグインをアンインストールしてください。',
    'admin'      => [
        'order_vendor'   => '注文ストア',
        'title'          => 'マルチベンダー',
        'help'           => '',
        'store_open'     => '営業/休業',
        'store_open_help'=> 'システム上でストアを営業/休業にします。休業中はストアのすべての活動がロックされます。',
        'store_config'   => 'ストア設定',
        'store_remove'   => 'ストアを削除',
        'store_shop'     => 'ショップ',
        'store_mode'     => 'ストアモード',
        'store_url'      => 'ショップへ移動',
        'note_admin_login' => '管理者アカウントでログイン中です。<br>管理者アカウントからログアウトしてください',
    ],
    // Heading of the "Top new vendors" storefront block (template/blocks/vendor_new).
    'top_new_vendor' => '新着ベンダー',
    // Labels for the storefront page-types this plugin registers into
    // config('gp247-config.front.layout_page') (see Provider.php) so admins can
    // attach LayoutBlock blocks to the vendor pages.
    'layout_block_page' => [
        'vendor_home'         => 'ベンダーホーム',
        'vendor_product_list' => 'ベンダー商品一覧',
        'vendor_index'        => 'ベンダー一覧',
    ],
    // Core payment requests: pay a period payout through a money-out request.
    'payreq_purpose' => 'ベンダーへの支払い（マーケットプレイス）',
    'payreq_create' => '支払いリクエストで支払う',
    'payreq_row_missing' => '支払い行が見つかりません',
    'payreq_row_not_payable' => 'リクエストで支払えるのは未払いの期間支払い（ステータスが処理中、金額が0より大きいもの）のみです',
    'payreq_row_currency' => '通貨は支払いの通貨（:currency）と同じである必要があります',
    'payreq_row_over' => 'この支払いの上限は:maxです',
    'payreq_root_only' => 'ベンダーへの支払いはマーケットプレイス（ルートストア）が行います',
    'payreq_subject_label' => '支払い行ID',
    'payreq_subject_help' => '最も簡単な方法：ベンダー支払い画面で支払い行を開き、「支払いリクエストで支払う」を使用してください',
];
