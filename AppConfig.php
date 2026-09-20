<?php
namespace App\GP247\Plugins\MultiVendor;

use App\GP247\Plugins\MultiVendor\Models\ExtensionModel;
use GP247\Core\Models\AdminConfig;
use GP247\Core\Models\AdminHome;
use GP247\Core\Models\AdminMenu;
use GP247\Core\Models\Languages;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use GP247\Core\ExtensionConfigDefault;
use App\GP247\Plugins\MultiVendor\Notifications\VendorNotifier;
use App\GP247\Plugins\MultiVendor\Dispute\Dispute;
use App\GP247\Plugins\MultiVendor\Kyc\Kyc;
use App\GP247\Plugins\MultiVendor\Orders\VendorOrderPolicy;
use App\GP247\Plugins\MultiVendor\Models\VendorOrderShipment;
use App\GP247\Plugins\MultiVendor\Models\VendorReviewLog;

class AppConfig extends ExtensionConfigDefault
{
    /**
     * Fired at the end of converge(). Uninstalling this plugin deletes every
     * language row of position `multi_vendor` — including the ones the paid
     * plugin seeded, which it cannot put back by itself because its install()
     * refuses to run twice. It listens for this instead. An event, not a call:
     * the free plugin is published in full and must not know the paid one
     * exists (rule gp247 §7a).
     */
    public const SEED_EVENT = 'multi_vendor.seed';

    public function __construct()
    {
        //Read config from config.json
        $config = file_get_contents(__DIR__.'/gp247.json');
        $config = json_decode($config, true);
    	$this->configGroup = $config['configGroup'];
        $this->configKey = $config['configKey'];
        $this->configCode = $config['configCode'] ?? $this->configKey;
        $this->requireCore = $config['requireCore'] ?? [];
        $this->requireComposerPackages = $config['requireComposerPackages'] ?? [];
        $this->requireGp247Extensions = $config['requireGp247Extensions'] ?? [];
        //Path
        $this->appPath = $this->configGroup . '/' . $this->configKey;
        //Language
        $this->title = trans($this->appPath.'::lang.title');
        //Image logo or thumb
        $this->image = $this->appPath.'/'.$config['image'];
        //
        $this->version = $config['version'];
        $this->auth = $config['auth'];
        $this->link = $config['link'];
    }

    public function install()
    {
        // S2-3: multi-vendor and multi-store are mutually exclusive business models
        // sharing admin_store (symmetric half of ADR multi-store_vendor-exclusion). The
        // core helper owns the multi-store key list (MultiStore / MultiStorePro). The
        // guard returns BEFORE any write — including the legacy-identity migration —
        // so a refused install leaves zero trace (NFR-AVAIL-install-guard-atomic).
        if (gp247_store_check_multi_store_installed()) {
            // WHY trans() on the file namespace: DB language rows are only seeded by
            // install itself, so they cannot exist yet.
            return ['error' => 1, 'msg' => trans($this->appPath.'::lang.conflict_multi_store')];
        }

        $return = ['error' => 0, 'msg' => ''];

        // S4-1: a site that ran the 1.x full plugin under the key MultiVendorPro is
        // adopted in place (config keys, menu uris, language codes renamed; tables kept).
        $adopted = self::migrateLegacyIdentity();

        $check = AdminConfig::where('key', $this->configKey)->first();
        if ($check) {
            //Check multi-vendor  exist
            $return = ['error' => 1, 'msg' =>  gp247_language_render('admin.extension.plugin_exist')];
        } else {
            //Insert plugin to config
            $dataInsert = [
                [
                    'group'  => $this->configGroup,
                    'code'    => $this->configCode,
                    'key'    => $this->configKey,
                    'sort'   => 0,
                    'store_id' => GP247_STORE_ID_GLOBAL,
                    'value'  => self::ON, //Enable extension
                    'detail' => $this->appPath.'::lang.title',
                ],
                [
                    'group'  => '',
                    'code'   => $this->configKey.'_config',
                    'key'    => 'MultiVendor_allow_register',
                    'sort'   => 0,
                    'store_id' => GP247_STORE_ID_GLOBAL,
                    'value'  => 1,
                    'detail' => 'multi_vendor.MultiVendor_allow_register',
                ],
                [
                    'group'  => '',
                    'code'   => $this->configKey.'_config',
                    'key'    => 'MultiVendor_product_auto_approve',
                    'sort'   => 0,
                    'store_id' => GP247_STORE_ID_GLOBAL,
                    'value'  => 0,
                    'detail' => 'multi_vendor.MultiVendor_product_auto_approve',
                ],
                [
                    'group'  => '',
                    'code'   => $this->configKey.'_config',
                    'key'    => 'MultiVendor_vendor_auto_approve',
                    'sort'   => 0,
                    'store_id' => GP247_STORE_ID_GLOBAL,
                    'value'  => 1,
                    'detail' => 'multi_vendor.MultiVendor_vendor_auto_approve',
                ],
                [
                    'group'  => '',
                    'code'   => $this->configKey.'_config',
                    'key'    => 'MultiVendor_commission',
                    'sort'   => 0,
                    'store_id' => GP247_STORE_ID_GLOBAL,
                    'value'  => 10,
                    'detail' => 'multi_vendor.MultiVendor_commission',
                ],
                [
                    'group'  => '',
                    'code'   => $this->configKey.'_config',
                    'key'    => 'MultiVendor_quick_order',
                    'sort'   => 0,
                    'store_id' => GP247_STORE_ID_GLOBAL,
                    'value'  => 1,
                    'detail' => 'multi_vendor.MultiVendor_quick_order',
                ]
            ];

            try {
                $process = self::seedConfigRows($dataInsert);

                $multiVendorBlock = AdminMenu::where('key', 'ADMIN_MVENDOR_SETTING')->first();
                if (!$multiVendorBlock) {
                    $idBlock = AdminMenu::insertGetId(
                        [
                            'parent_id' => 0,
                            'sort'      => 250,
                            'title'     => gp247_language_render('multi_vendor.plugin_block'),
                            'icon'      => 'nav-icon fab fa-shopify',
                            'key'       => 'ADMIN_MVENDOR_SETTING',
                        ]
                    );
                    // WHY: seed the child menu items only when the block is newly
                    // created. AdminMenu::insert() is not idempotent, so running
                    // install() while the block already exists (an inconsistent
                    // partial state) duplicated every child — the marketplace
                    // sidebar then showed each item multiple times.
                    $dataInsert = [
                        [
                            'parent_id' => $idBlock,
                            'sort'      => 1,
                            'title'     => gp247_language_render('multi_vendor.vendor_store'),
                            'icon'      => 'fas fa-store-alt',
                            'uri'       => 'admin::MultiVendor/store'
                        ],
                        [
                            'parent_id' => $idBlock,
                            'sort'      => 2,
                            'title'     => gp247_language_render('multi_vendor.vendor_user'),
                            'icon'      => 'fa fa-user-circle',
                            'uri'       => 'admin::MultiVendor/vendor'
                        ],
                        [
                            'parent_id' => $idBlock,
                            'sort'      => 3,
                            'title'     => gp247_language_render('multi_vendor.vendor_config'),
                            'icon'      => 'fas fa-cogs ',
                            'uri'       => 'admin::MultiVendor/config'
                        ],
                        [
                            'parent_id' => $idBlock,
                            'sort'      => 4,
                            'title'     => gp247_language_render('multi_vendor.vendor_payment'),
                            'icon'      => 'far fa-money-bill-alt ',
                            'uri'       => 'admin::MultiVendor/payment'
                        ]
                    ];
                    // S4-1: Report (sort 5) and Review queue (sort 6) are Pro screens — the
                    // MultiVendorPro unlock plugin seeds/removes their menu items.
                    AdminMenu::insert(
                        $dataInsert
                    );
                }

                $dataLang = [
                    ['code' => 'multi_vendor.plugin_block', 'text' => 'MARKETPLACE', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.plugin_block', 'text' => 'CHỢ BÁN HÀNG', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.account_inactive_title', 'text' => 'Access denied!', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.account_inactive_title', 'text' => 'Truy cập bị từ chối', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.account_inactive_msg', 'text' => 'The account has not been activated or has been locked!', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.account_inactive_msg', 'text' => 'Tài khoản chưa được kích hoạt hoặc đã bị khóa!', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.update_info_store_msg', 'text' => 'Please update your store information!', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.update_info_store_msg', 'text' => 'Vui lòng cập nhật thông tin cửa hàng của bạn!', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.update_info_store_title', 'text' => 'Update store information', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.update_info_store_title', 'text' => 'Cập nhật thông tin cửa hàng', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.vendor_store', 'text' => 'Vendor store', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.vendor_store', 'text' => 'Gian hàng người bán', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.store_code', 'text' => 'Store code', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.store_code', 'text' => 'Mã cửa hàng', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.store_code_placeholder', 'text' => 'Enter store code', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.store_code_placeholder', 'text' => 'Nhập mã cửa hàng', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.vendor_user', 'text' => 'Vendor user', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.vendor_user', 'text' => 'Tài khoản người bán', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.vendor_config', 'text' => 'Quick Configuration', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.vendor_config', 'text' => 'Cấu hình nhanh', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.vendor_report', 'text' => 'Report', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.vendor_report', 'text' => 'Báo cáo', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.vendor_add', 'text' => 'Add new vendor', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.vendor_add', 'text' => 'Thêm người bán', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.config', 'text' => 'Config information', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.config', 'text' => 'Thông tin cấu hình', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.vendor_search_place', 'text' => 'Search name, email vendor', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.vendor_search_place', 'text' => 'Tìm kiếm tên, email', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.password', 'text' => 'Password', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.password', 'text' => 'Mật khẩu', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.password_forgot', 'text' => 'Forgot password', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.password_forgot', 'text' => 'Quên mật khẩu', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.login_title', 'text' => 'Login page', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.login_title', 'text' => 'Trang đăng nhập', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.register_success', 'text' => 'Successful register', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.register_success', 'text' => 'Đăng ký thành công', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.signup', 'text' => 'Signup', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.signup', 'text' => 'Đăng ký', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.title_register', 'text' => 'Account register', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.title_register', 'text' => 'Đăng ký tài khoản', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.password_reset', 'text' => 'Password reset', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.password_reset', 'text' => 'Reset mật khẩu', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.password_confirm', 'text' => 'Password confirm', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.password_confirm', 'text' => 'Xác nhận mật khẩu', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.phone_regex', 'text' => 'The phone format is not correct. Length 8-14, use only 0-9 and the "-" SIGN.', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.phone_regex', 'text' => 'Số điện thoại định dạng không đúng. Chiều dài 8-14, chỉ sử dụng số 0-9 và "-"', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.store_id_required', 'text' => 'Tài khoản vendor phải được liên kết với một cửa hàng. Vui lòng liên hệ quản trị viên.', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.store_id_required', 'text' => 'Vendor account must be associated with a store. Please contact administrator.', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.country', 'text' => 'Country', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.country', 'text' => 'Quốc gia', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.address2', 'text' => 'Quận/Huyện', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.address2', 'text' => 'Address 2', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.address1', 'text' => 'Tỉnh/Thành', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.address1', 'text' => 'Address 1', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.postcode', 'text' => 'Mã bưu điện', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.postcode', 'text' => 'Post code', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.phone', 'text' => 'Phone', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.phone', 'text' => 'Điện thoại', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.name', 'text' => 'Name', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.name', 'text' => 'Tên', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.last_name', 'text' => 'Họ', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.last_name', 'text' => 'Last name', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.first_name', 'text' => 'Tên', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.first_name', 'text' => 'First name', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.email', 'text' => 'Email', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.email', 'text' => 'Email', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.title_login', 'text' => 'Login account', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.title_login', 'text' => 'Đăng nhập', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.status', 'text' => 'Trạng thái', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.status', 'text' => 'Status', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.group', 'text' => 'Nhóm', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.group', 'text' => 'Group', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.admin_login', 'text' => 'Vendor login', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.admin_login', 'text' => 'Dành cho người bán hàng', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.admin.keep_password', 'text' => 'Leave it blank if you don\'t change the password', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.admin.keep_password', 'text' => 'Để trống nếu không thay đổi mật khẩu', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.remember_me', 'text' => 'Remember me', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.remember_me', 'text' => 'Ghi nhớ tài khoản', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.login', 'text' => 'Login', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.login', 'text' => 'Đăng nhập', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.top_count_order_vendor', 'text' => 'Top stores with the highest number of orders', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.top_count_order_vendor', 'text' => 'Top cửa hàng có số đơn hàng cao nhất', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.export_order_list', 'text' => 'Export order list', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.export_order_list', 'text' => 'Export đơn hàng ', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.store_empty', 'text' => 'Select store', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.store_empty', 'text' => 'Chọn cửa hàng ', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.quick_order', 'text' => 'Quick order', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.quick_order', 'text' => 'Đặt hàng nhanh', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.MultiVendor_commission', 'text' => 'Commission rate', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.MultiVendor_commission', 'text' => 'Tỷ lệ hoa hồng', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.MultiVendor_commission_help', 'text' => 'Is the payment rate (%) that the trading floor will keep before paying the vendor', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.MultiVendor_commission_help', 'text' => 'Là tỉ lệ thanh toán (%) mà sàn thương mại sẽ giữ lại trước khi chi trả cho nhà cung cấp', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.MultiVendor_quick_order', 'text' => 'Quick order', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.MultiVendor_quick_order', 'text' => 'Đặt hàng nhanh', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.MultiVendor_quick_order_help', 'text' => 'The function allows customers to place bulk orders on each vendor\'s booth.', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.MultiVendor_quick_order_help', 'text' => 'Chức năng cho phép khách hàng đặt hàng số lượng lớn trên mỗi gian hàng của nhà cung cấp.', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.MultiVendor_allow_register', 'text' => 'Allow register vendor', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.MultiVendor_allow_register', 'text' => 'Cho phép đăng ký vendor', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.MultiVendor_allow_register_help', 'text' => 'Users are allowed to self-register for a vendor account. If disabled, the vendor account can only be registered through admin.', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.MultiVendor_allow_register_help', 'text' => 'Người dùng được phép tự đăng ký tài khoản vendor. Nếu vô hiệu hóa, tài khoản vendor chỉ có thể đăng ký thông qua admin.', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.MultiVendor_product_auto_approve', 'text' => 'Auto approve product', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.MultiVendor_product_auto_approve', 'text' => 'Tự động duyệt sản phẩm', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.MultiVendor_product_auto_approve_help', 'text' => 'If this is disabled, the admin must approve all products posted by the seller, including products edited.', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.MultiVendor_product_auto_approve_help', 'text' => 'Nếu tắt chức nằng này, admin phải phê duyệt tất cả sản phẩm được đăng bởi người bán, bao gồm sản phẩm chỉnh sửa lại.', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.MultiVendor_vendor_auto_approve', 'text' => 'Auto approve vendor', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.MultiVendor_vendor_auto_approve', 'text' => 'Tự động duyệt vendor', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.MultiVendor_vendor_auto_approve_help', 'text' => 'If this is disabled, the admin must approve all new vendors.', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.MultiVendor_vendor_auto_approve_help', 'text' => 'Nếu tắt chức nằng này, admin phải phê duyệt tất cả nhà cung cấp.', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.MultiVendor_config', 'text' => 'Quick Configuration', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.MultiVendor_config', 'text' => 'Cấu hình nhanh', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.vendor_payment', 'text' => 'Payment', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.vendor_payment', 'text' => 'Thanh toán', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.vendor_payment_date', 'text' => 'Payment processing date', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.vendor_payment_date', 'text' => 'Ngày xử lý thanh toán', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.vendor_payment_date_help', 'text' => 'The processing date must be less than the current date.<br>Processing only completed orders.', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.vendor_payment_date_help', 'text' => 'Ngày xử lý phải nhỏ hơn ngày hiện tại.<br>Chỉ xử lý đơn hàng đã finish.', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.vendor_payment_button', 'text' => 'Process', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.vendor_payment_button', 'text' => 'Xử lý', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.vendor_payment_date_validate', 'text' => 'Time must be less than current date', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.vendor_payment_date_validate', 'text' => 'Thời gian phải nhỏ hơn ngày hiện tại', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.vendor_payment_date_exist', 'text' => 'The date you selected has already been processed', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.vendor_payment_date_exist', 'text' => 'Ngày bạn chọn đã xử lý rồi', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.payment.total_sum', 'text' => 'Total sum', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.payment.total_sum', 'text' => 'Tổng giá trị', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.payment.amount', 'text' => 'Amount', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.payment.amount', 'text' => 'Thanh toán', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.payment.order_count', 'text' => 'Orders', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.payment.order_count', 'text' => 'Đơn hàng', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.payment.currency', 'text' => 'Currency', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.payment.currency', 'text' => 'Tiền tệ', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.payment.content', 'text' => 'Content', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.payment.content', 'text' => 'Nội dung', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.payment.comment', 'text' => 'Comment', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.payment.comment', 'text' => 'Bình luận', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.payment.date_process', 'text' => 'Date process', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.payment.date_process', 'text' => 'Ngày xử lý', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.payment.date_pay', 'text' => 'Date pay', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.payment.date_pay', 'text' => 'Ngày thanh toán', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.payment.status', 'text' => 'Status', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.payment.status', 'text' => 'Trạng thái', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.payment.created_at', 'text' => 'Created at', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.payment.created_at', 'text' => 'Tạo lúc', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.payment.life_time', 'text' => 'Lifetime sales', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.payment.life_time', 'text' => 'Đã bán', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.payment.payout', 'text' => 'Payout', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.payment.payout', 'text' => 'Đã nhận', 'position' => 'multi_vendor', 'location' => 'vi'],
                    ['code' => 'multi_vendor.payment.remaining', 'text' => 'Remaining', 'position' => 'multi_vendor', 'location' => 'en'],
                    ['code' => 'multi_vendor.payment.remaining', 'text' => 'Còn lại', 'position' => 'multi_vendor', 'location' => 'vi'],
                ];

                Languages::insertOrIgnore(
                    $dataLang
                );

                // S1-2 notification flags + their i18n (idempotent; shared with update()).
                self::seedNotificationSettings();

                if (!$process) {
                    $return = ['error' => 1, 'msg' => gp247_language_render('plugin.plugin_action.install_faild')];
                } else {
                    if ($adopted) {
                        // Tables already exist with live data — never drop-and-create; run the
                        // re-entrant pieces only.
                        (new \App\GP247\Plugins\MultiVendor\Models\VendorOrderShipment)->install();
                        (new \App\GP247\Plugins\MultiVendor\Models\VendorReviewLog)->install();
                        \App\GP247\Plugins\MultiVendor\Admin\Models\AdminMoneyProcess::upgradeSchema();
                    } else {
                        (new ExtensionModel)->installExtension();
                    }
                    if (is_writable(base_path('app/GP247/Templates/'.gp247_store_info('template')))) {
                        
                        if(!File::isDirectory(base_path('app/GP247/Templates/'.gp247_store_info('template').'/blocks'))){
                            File::makeDirectory(base_path('app/GP247/Templates/'.gp247_store_info('template').'/blocks'));
                        }
                        foreach (glob(app_path('GP247/Plugins/'.$this->configKey.'/template/blocks/*.blade.php')) as $filename) {
                            $checkFile = str_replace(
                                app_path('GP247/Plugins/'.$this->configKey.'/template'),
                                app_path('GP247/Templates/'.gp247_store_info('template')),
                                $filename
                            );
                            if (!file_exists($checkFile)) {
                                File::copy($filename, $checkFile);
                            }
                        }
                    }
                    // Same convergence list update() runs. A fresh install and a
                    // reinstall must end in the same place; see converge().
                    self::converge();
                }
                $return = ['error' => 0, 'msg' => gp247_language_render('admin.extension.install_success')];
            } catch(\Throwable $e) {
                $this->uninstall();
                $return = ['error' => 1, 'msg' => $e->getMessage()];
            }

        }

        return $return;
    }

    /**
     * Data migration on version update (plugin format ≥ 2.0). 1.0 → 1.1 adds the
     * four notification flags and their language rows; insertOrIgnore keeps it
     * idempotent so re-running is harmless.
     *
     * @param string|null $fromVersion
     * @return array
     */
    public function update(?string $fromVersion = null)
    {
        try {
            self::migrateLegacyIdentity();
            self::seedNotificationSettings();
            (new VendorOrderShipment)->install(); // S1-3 table, re-entrant
            (new VendorReviewLog)->install(); // S1-4 table, re-entrant
            \App\GP247\Plugins\MultiVendor\Admin\Models\AdminMoneyProcess::upgradeSchema(); // S1-5 decimal money + payout columns, idempotent (+ S3-1 kind/parent_id)
            self::converge();
        } catch (\Throwable $e) {
            return ['error' => 1, 'msg' => $e->getMessage()];
        }

        return ['error' => 0, 'msg' => ''];
    }

    /**
     * Seed the notification flags (global admin_config, default ON) and the mail
     * language rows. Safe to call any time: insertOrIgnore on both tables.
     *
     * WHY public static: RootConfigForm::mount() calls it as a safety net for sites
     * updated by plain file replacement (ConfigForm::save() only UPDATEs rows).
     */
    public static function seedNotificationSettings(): void
    {
        $configKey = 'MultiVendor';
        $rows = [];
        foreach (VendorNotifier::EVENTS as $i => $event) {
            $rows[] = [
                'group'    => '',
                'code'     => $configKey.'_config',
                'key'      => VendorNotifier::configKey($event),
                'sort'     => 10 + $i,
                'store_id' => GP247_STORE_ID_GLOBAL,
                'value'    => 1,
                'detail'   => 'multi_vendor.'.VendorNotifier::configKey($event),
            ];
        }
        // S1-3: vendor order scope preset (select), default = confirm.
        $rows[] = [
            'group'    => '',
            'code'     => $configKey.'_config',
            'key'      => VendorOrderPolicy::CONFIG_KEY,
            'sort'     => 20,
            'store_id' => GP247_STORE_ID_GLOBAL,
            'value'    => VendorOrderPolicy::DEFAULT_SCOPE,
            'detail'   => 'multi_vendor.'.VendorOrderPolicy::CONFIG_KEY,
        ];
        // S3-2: KYC required (bool, Pro), default off.
        $rows[] = [
            'group'    => '',
            'code'     => $configKey.'_config',
            'key'      => Kyc::CONFIG_KEY,
            'sort'     => 21,
            'store_id' => GP247_STORE_ID_GLOBAL,
            'value'    => 0,
            'detail'   => 'multi_vendor.'.Kyc::CONFIG_KEY,
        ];
        // S3-3: dispute window / vendor answer days (numbers, Pro). The mail flag rides
        // on VendorNotifier::EVENTS above.
        foreach ([[Dispute::CONFIG_WINDOW_DAYS, Dispute::DEFAULT_WINDOW_DAYS, 22], [Dispute::CONFIG_VENDOR_DAYS, Dispute::DEFAULT_VENDOR_DAYS, 23]] as [$key, $value, $sort]) {
            $rows[] = [
                'group'    => '',
                'code'     => $configKey.'_config',
                'key'      => $key,
                'sort'     => $sort,
                'store_id' => GP247_STORE_ID_GLOBAL,
                'value'    => $value,
                'detail'   => 'multi_vendor.'.$key,
            ];
        }
        self::seedConfigRows($rows);

        $l = function (string $code, string $en, string $vi) {
            return [
                ['code' => $code, 'text' => $en, 'position' => 'multi_vendor', 'location' => 'en'],
                ['code' => $code, 'text' => $vi, 'position' => 'multi_vendor', 'location' => 'vi'],
            ];
        };
        $lang = array_merge(
            $l('multi_vendor.MultiVendor_mail_order_created', 'Email vendor on new order', 'Email cho vendor khi có đơn mới'),
            $l('multi_vendor.MultiVendor_mail_order_created_help', 'Send every active vendor account of the store an e-mail when an order for that store is created.', 'Gửi email cho mọi tài khoản vendor đang hoạt động của gian hàng khi có đơn hàng mới thuộc gian hàng đó.'),
            $l('multi_vendor.MultiVendor_mail_pending_review', 'Email admin on pending review', 'Email cho admin khi có mục chờ duyệt'),
            $l('multi_vendor.MultiVendor_mail_pending_review_help', 'Send the marketplace e-mail when a new vendor or a vendor product is waiting for approval.', 'Gửi email của sàn khi có vendor mới hoặc sản phẩm vendor đang chờ duyệt.'),
            $l('multi_vendor.MultiVendor_mail_vendor_approved', 'Email vendor when approved', 'Email cho vendor khi được duyệt'),
            $l('multi_vendor.MultiVendor_mail_vendor_approved_help', 'Send the vendor an e-mail when the admin opens their store.', 'Gửi email cho vendor khi admin mở gian hàng của họ.'),
            $l('multi_vendor.MultiVendor_mail_payout_done', 'Email vendor when payout is done', 'Email cho vendor khi đã trả tiền'),
            $l('multi_vendor.MultiVendor_mail_payout_done_help', 'Send the vendor an e-mail when a payout row is marked done.', 'Gửi email cho vendor khi một dòng sổ thanh toán được đánh dấu đã trả.'),
            $l('multi_vendor.store', 'Store', 'Gian hàng'),
            $l('multi_vendor.mail.order_created.subject', 'New order #:order_id for your store', 'Đơn hàng mới #:order_id cho gian hàng của bạn'),
            $l('multi_vendor.mail.order_created.title', 'New order for :store', 'Đơn hàng mới cho :store'),
            $l('multi_vendor.mail.order_created.intro', 'A customer has just placed order #:order_id containing your products. Please prepare the shipment.', 'Khách hàng vừa đặt đơn #:order_id có sản phẩm của bạn. Vui lòng chuẩn bị giao hàng.'),
            $l('multi_vendor.mail.order_created.button', 'View order', 'Xem đơn hàng'),
            $l('multi_vendor.mail.pending_review.subject_vendor', 'New vendor waiting for approval: :store', 'Vendor mới chờ duyệt: :store'),
            $l('multi_vendor.mail.pending_review.subject_product', 'Product waiting for approval from :store', 'Sản phẩm chờ duyệt từ :store'),
            $l('multi_vendor.mail.pending_review.title_vendor', 'A new vendor is waiting for approval', 'Có vendor mới chờ duyệt'),
            $l('multi_vendor.mail.pending_review.title_product', 'A vendor product is waiting for approval', 'Có sản phẩm vendor chờ duyệt'),
            $l('multi_vendor.mail.pending_review.label_vendor', 'Vendor e-mail', 'Email vendor'),
            $l('multi_vendor.mail.pending_review.label_product', 'Product', 'Sản phẩm'),
            $l('multi_vendor.mail.pending_review.code', 'Reference', 'Mã tham chiếu'),
            $l('multi_vendor.mail.pending_review.action_vendor', 'Open Marketplace (Pro) → Stores and switch the store on to approve it.', 'Vào Chợ online (Pro) → Gian hàng và bật trạng thái để duyệt.'),
            $l('multi_vendor.mail.pending_review.action_product', 'Open Products in the admin, filter unapproved products and approve it.', 'Vào Sản phẩm trong admin, lọc sản phẩm chưa duyệt và duyệt.'),
            $l('multi_vendor.mail.vendor_approved.subject', 'Your store :store has been approved', 'Gian hàng :store của bạn đã được duyệt'),
            $l('multi_vendor.mail.vendor_approved.title', ':store is now open', ':store đã mở bán'),
            $l('multi_vendor.mail.vendor_approved.intro', 'The marketplace has approved your store. You can now sign in, post products and receive orders.', 'Sàn đã duyệt gian hàng của bạn. Bạn có thể đăng nhập, đăng sản phẩm và nhận đơn.'),
            $l('multi_vendor.mail.vendor_approved.store_url', 'Your store page', 'Trang gian hàng của bạn'),
            $l('multi_vendor.mail.vendor_approved.button', 'Sign in to vendor admin', 'Đăng nhập khu vendor'),
            $l('multi_vendor.mail.payout_done.subject', 'Payout completed for :store', 'Đã trả tiền kỳ thanh toán cho :store'),
            $l('multi_vendor.mail.payout_done.title', 'Payout completed — :store', 'Đã trả tiền — :store'),
            $l('multi_vendor.mail.payout_done.intro', 'The marketplace has marked the following payout as paid.', 'Sàn đã đánh dấu kỳ thanh toán dưới đây là đã trả.'),
            $l('multi_vendor.mail.payout_done.vendor_share', 'Your share', 'Tỷ lệ bạn nhận'),
            $l('multi_vendor.mail.payout_done.button', 'View payout history', 'Xem lịch sử thanh toán')
        );
        Languages::insertOrIgnore($lang);
        self::seedCommissionLanguage();
        self::seedOrderFulfillmentLanguage();
        self::seedReviewLanguage();
        self::seedPayoutLanguage();
        self::seedTierLanguage();
        self::seedShopPageLanguage();
        self::seedClawbackLanguage();
        self::seedKycLanguage();
        self::seedDisputeLanguage();
        self::seedPlanLanguage();
        self::seedTrustSignalLanguage();
        self::seedPlanSelfServiceLanguage();
    }

    /**
     * Language rows for the per-vendor commission UI (S1-1). Called from
     * seedNotificationSettings() so install/update/RootConfigForm::mount() keep one
     * idempotent entry point.
     */
    public static function seedCommissionLanguage(): void
    {
        $l = function (string $code, string $en, string $vi) {
            return [
                ['code' => $code, 'text' => $en, 'position' => 'multi_vendor', 'location' => 'en'],
                ['code' => $code, 'text' => $vi, 'position' => 'multi_vendor', 'location' => 'vi'],
            ];
        };
        Languages::insertOrIgnore(array_merge(
            $l('multi_vendor.commission_override', 'Store commission (%)', 'Hoa hồng riêng của gian hàng (%)'),
            $l('multi_vendor.commission_override_help', 'Leave empty to follow the marketplace rate (:rate%). Applies to payout periods processed after the change.', 'Để trống = theo tỷ lệ sàn (:rate%). Áp dụng cho các kỳ thanh toán xử lý sau khi đổi.'),
            $l('multi_vendor.commission_invalid', 'Commission must be a number from 0 to 100.', 'Hoa hồng phải là số từ 0 đến 100.'),
            $l('multi_vendor.commission_column', 'Commission', 'Hoa hồng'),
            $l('multi_vendor.commission_marketplace', '(marketplace)', '(sàn)'),
            $l('multi_vendor.commission_current', 'Commission currently applied to your store: :rate% (you receive :share%).', 'Hoa hồng hiện áp dụng cho gian hàng: :rate% (bạn nhận :share%).')
        ));
    }

    /**
     * Language rows for vendor order fulfilment (S1-3): scope preset labels, order
     * transition / shipment UI, history lines. Called from seedNotificationSettings().
     */
    /**
     * Adopt a site that installed the 1.x full plugin under the key `MultiVendorPro`
     * (S4-1, ADR multi-vendor_free-pro-split). Idempotent; returns true when
     * legacy data was found (tables exist / legacy rows renamed).
     *
     * What moves: admin_config keys `MultiVendorPro_*` → `MultiVendor_*` (all store
     * tiers) and their `detail` i18n codes; language codes
     * `multi_vendor.MultiVendorPro_*` → `multi_vendor.MultiVendor_*`; sidebar uris
     * `admin::MultiVendorPro/*` → `admin::MultiVendor/*`. The legacy registration
     * row `MultiVendorPro` is removed ONLY when it belonged to the old full plugin
     * (no thin unlock plugin present) — with the unlock plugin installed that same
     * key now means "Pro is on" and must stay.
     *
     * Plugin-owned tables (vendor_*) are never touched.
     */
    public static function migrateLegacyIdentity(): bool
    {
        $legacyRows = AdminConfig::where('key', 'like', 'MultiVendorPro\_%')->count();
        $legacyMenu = AdminMenu::where('uri', 'like', 'admin::MultiVendorPro/%')->count();
        $tables = Schema::hasTable('vendor_user');
        if ($legacyRows === 0 && $legacyMenu === 0) {
            return $tables && AdminConfig::where('key', 'MultiVendor')->doesntExist();
        }

        AdminConfig::where('key', 'like', 'MultiVendorPro\_%')->get()->each(function ($row) {
            $row->key = 'MultiVendor_'.substr($row->key, strlen('MultiVendorPro_'));
            if (is_string($row->detail) && str_starts_with($row->detail, 'multi_vendor.MultiVendorPro_')) {
                $row->detail = 'multi_vendor.MultiVendor_'.substr($row->detail, strlen('multi_vendor.MultiVendorPro_'));
            }
            $row->save();
        });
        Languages::where('code', 'like', 'multi\_vendor.MultiVendorPro\_%')->get()->each(function ($row) {
            $row->code = 'multi_vendor.MultiVendor_'.substr($row->code, strlen('multi_vendor.MultiVendorPro_'));
            $row->save();
        });
        AdminMenu::where('uri', 'like', 'admin::MultiVendorPro/%')->get()->each(function ($row) {
            $row->uri = 'admin::MultiVendor/'.substr($row->uri, strlen('admin::MultiVendorPro/'));
            $row->save();
        });

        // The old full plugin registered itself as MultiVendorPro. Drop that row unless a
        // thin unlock plugin (requireGp247Extensions: MultiVendor) is what lives there now.
        $unlockJson = app_path('GP247/Plugins/MultiVendorPro/gp247.json');
        $isUnlock = false;
        if (is_file($unlockJson)) {
            $json = json_decode((string) file_get_contents($unlockJson), true) ?: [];
            $isUnlock = in_array('MultiVendor', $json['requireGp247Extensions'] ?? [], true);
        }
        if (!$isUnlock) {
            AdminConfig::where('key', 'MultiVendorPro')->where('group', 'Plugins')->delete();
        }

        return true;
    }

    /**
     * Language rows for the Free/Pro edition hints (S4-1).
     */
    /**
     * i18n rows of the Shopee-style shop page and the vendor directory (S2-5).
     */
    public static function seedShopPageLanguage(): void
    {
        $l = function (string $code, string $en, string $vi) {
            return [
                ['code' => $code, 'text' => $en, 'position' => 'multi_vendor', 'location' => 'en'],
                ['code' => $code, 'text' => $vi, 'position' => 'multi_vendor', 'location' => 'vi'],
            ];
        };
        Languages::insertOrIgnore(array_merge(
            $l('multi_vendor.shop.tab_products', 'Products', 'Sản phẩm'),
            $l('multi_vendor.shop.tab_reviews', 'Reviews', 'Đánh giá'),
            $l('multi_vendor.shop.tab_info', 'About the store', 'Thông tin gian hàng'),
            $l('multi_vendor.shop.products_count', 'products', 'sản phẩm'),
            $l('multi_vendor.shop.rating_count', ':count reviews', ':count đánh giá'),
            $l('multi_vendor.shop.no_rating', 'No reviews yet', 'Chưa có đánh giá'),
            $l('multi_vendor.shop.joined', 'Member since', 'Tham gia'),
            $l('multi_vendor.shop.search', 'Search', 'Tìm'),
            $l('multi_vendor.shop.search_placeholder', 'Search in this store', 'Tìm trong gian hàng'),
            $l('multi_vendor.shop.clear_filter', 'Clear', 'Bỏ lọc'),
            $l('multi_vendor.shop.all_categories', 'All', 'Tất cả'),
            $l('multi_vendor.shop.address', 'Address', 'Địa chỉ'),
            $l('multi_vendor.shop.phone', 'Phone', 'Điện thoại'),
            $l('multi_vendor.shop.email', 'E-mail', 'Email'),
            $l('multi_vendor.shop.hours', 'Opening hours', 'Giờ mở cửa'),
            $l('multi_vendor.shop.directory_title', 'Stores', 'Gian hàng'),
            $l('multi_vendor.shop.directory_search', 'Search stores by name', 'Tìm gian hàng theo tên'),
            $l('multi_vendor.shop.no_stores', 'No stores found.', 'Không có gian hàng phù hợp.')
        ));
    }

    /**
     * i18n rows of the payout clawback (S3-1): mail flag, ledger kinds, mail body.
     */
    public static function seedClawbackLanguage(): void
    {
        $l = function (string $code, string $en, string $vi) {
            return [
                ['code' => $code, 'text' => $en, 'position' => 'multi_vendor', 'location' => 'en'],
                ['code' => $code, 'text' => $vi, 'position' => 'multi_vendor', 'location' => 'vi'],
            ];
        };
        Languages::insertOrIgnore(array_merge(
            $l('multi_vendor.MultiVendor_mail_payout_clawback', 'Email vendor on payout adjustment', 'Email cho vendor khi có điều chỉnh thanh toán'),
            $l('multi_vendor.MultiVendor_mail_payout_clawback_help', 'Send the vendor an e-mail when a paid order is refunded/canceled and its share is clawed back (netted into the next payout).', 'Gửi email cho vendor khi đơn đã trả bị hoàn/hủy và phần của vendor bị thu hồi (bù trừ ở kỳ thanh toán kế tiếp).'),
            $l('multi_vendor.clawback.kind', 'Type', 'Loại'),
            $l('multi_vendor.clawback.kind_period', 'Payout period', 'Kỳ thanh toán'),
            $l('multi_vendor.clawback.kind_clawback', 'Clawback', 'Thu hồi'),
            $l('multi_vendor.clawback.kind_refund', 'Partial refund', 'Hoàn một phần'),
            $l('multi_vendor.clawback.kind_reversal', 'Reversal', 'Đảo thu hồi'),
            $l('multi_vendor.commission_report.clawback', 'Adjustments', 'Điều chỉnh'),
            $l('multi_vendor.mail.payout_clawback.subject', 'Payout adjustment for :store', 'Điều chỉnh thanh toán cho :store'),
            $l('multi_vendor.mail.payout_clawback.title', 'Payout adjustment — :store', 'Điều chỉnh thanh toán — :store'),
            $l('multi_vendor.mail.payout_clawback.intro', 'An order the marketplace already paid you for was refunded or canceled. The amount below is booked against your store.', 'Một đơn sàn đã trả tiền cho bạn vừa bị hoàn tiền hoặc hủy. Số tiền dưới đây được ghi điều chỉnh cho gian hàng của bạn.'),
            $l('multi_vendor.mail.payout_clawback.netting', 'Nothing is charged to you now: the adjustment is netted into your next payout period.', 'Bạn không phải trả lại ngay: khoản điều chỉnh sẽ được bù trừ ở kỳ thanh toán kế tiếp.')
        ));
    }

    /**
     * i18n rows of vendor KYC (S3-2): flag, vendor screen, queue tab, badge, mail.
     */
    public static function seedKycLanguage(): void
    {
        $l = function (string $code, string $en, string $vi) {
            return [
                ['code' => $code, 'text' => $en, 'position' => 'multi_vendor', 'location' => 'en'],
                ['code' => $code, 'text' => $vi, 'position' => 'multi_vendor', 'location' => 'vi'],
            ];
        };
        Languages::insertOrIgnore(array_merge(
            $l('multi_vendor.MultiVendor_kyc_required', 'Require vendor identity verification (KYC)', 'Bắt buộc xác minh danh tính vendor (KYC)'),
            $l('multi_vendor.MultiVendor_kyc_required_help', 'When on, a store whose identity profile is not approved cannot get products approved and its payout rows are held as "pending" until verified.', 'Khi bật, gian hàng chưa được duyệt hồ sơ danh tính sẽ không được duyệt sản phẩm và dòng thanh toán bị giữ ở "pending" cho tới khi xác minh.'),
            $l('multi_vendor.kyc.title', 'Identity verification', 'Xác minh danh tính'),
            $l('multi_vendor.kyc.tab', 'Verification', 'Xác minh'),
            $l('multi_vendor.kyc.help', 'Tell the marketplace who runs this store. Identifiers are stored encrypted and only the marketplace owner can read them.', 'Cho sàn biết ai đứng sau gian hàng này. Mã số định danh được lưu mã hoá, chỉ chủ sàn đọc được.'),
            $l('multi_vendor.kyc.required_notice', 'This marketplace requires identity verification: until approved, your products cannot go live and payouts are held.', 'Sàn yêu cầu xác minh danh tính: cho tới khi được duyệt, sản phẩm của bạn không lên sàn và thanh toán bị giữ lại.'),
            $l('multi_vendor.kyc.status', 'Status', 'Trạng thái'),
            $l('multi_vendor.kyc.status_none', 'Not submitted', 'Chưa nộp'),
            $l('multi_vendor.kyc.status_pending', 'Waiting for review', 'Chờ duyệt'),
            $l('multi_vendor.kyc.status_approved', 'Verified', 'Đã xác minh'),
            $l('multi_vendor.kyc.status_rejected', 'Rejected', 'Bị từ chối'),
            $l('multi_vendor.kyc.verified', 'Verified', 'Đã xác minh'),
            $l('multi_vendor.kyc.submitted_at', 'Submitted', 'Nộp lúc'),
            $l('multi_vendor.kyc.rejected_reason', 'Rejection reason', 'Lý do từ chối'),
            $l('multi_vendor.kyc.type', 'Seller type', 'Loại người bán'),
            $l('multi_vendor.kyc.type_individual', 'Individual', 'Cá nhân'),
            $l('multi_vendor.kyc.type_company', 'Company', 'Doanh nghiệp'),
            $l('multi_vendor.kyc.legal_name', 'Legal name', 'Tên pháp lý'),
            $l('multi_vendor.kyc.tax_id', 'Tax id / registration no.', 'Mã số thuế / ĐKKD'),
            $l('multi_vendor.kyc.id_number', 'ID number', 'Số CMND/CCCD'),
            $l('multi_vendor.kyc.representative', 'Representative', 'Người đại diện'),
            $l('multi_vendor.kyc.address', 'Registered address', 'Địa chỉ đăng ký'),
            $l('multi_vendor.kyc.note', 'Note to the marketplace', 'Ghi chú cho sàn'),
            $l('multi_vendor.kyc.keep_hint', 'Leave empty to keep the stored value.', 'Để trống để giữ giá trị đã lưu.'),
            $l('multi_vendor.kyc.submit', 'Submit for review', 'Nộp hồ sơ'),
            $l('multi_vendor.kyc.resubmit', 'Update & resubmit', 'Cập nhật & nộp lại'),
            $l('multi_vendor.kyc.submitted', 'Your identity profile was submitted for review.', 'Hồ sơ danh tính đã được nộp, chờ sàn duyệt.'),
            $l('multi_vendor.kyc.approved', 'Identity profile approved — the store is now verified.', 'Đã duyệt hồ sơ — gian hàng đã được xác minh.'),
            $l('multi_vendor.kyc.blocked_product', 'This store has not passed identity verification; its products cannot be approved yet.', 'Gian hàng chưa xác minh danh tính; chưa thể duyệt sản phẩm.'),
            $l('multi_vendor.kyc.blocked_badge', 'KYC pending', 'Chờ KYC'),
            $l('multi_vendor.kyc.notice_required', 'Identity verification is required before your products go live and payouts are released.', 'Cần xác minh danh tính trước khi sản phẩm lên sàn và được thanh toán.'),
            $l('multi_vendor.kyc.notice_pending', 'Your identity profile is waiting for the marketplace review.', 'Hồ sơ danh tính của bạn đang chờ sàn duyệt.'),
            $l('multi_vendor.kyc.notice_action', 'Verify now', 'Xác minh ngay'),
            $l('multi_vendor.mail.kyc_reviewed.subject_approved', 'Store :store is verified', 'Gian hàng :store đã được xác minh'),
            $l('multi_vendor.mail.kyc_reviewed.subject_rejected', 'Identity profile of :store needs changes', 'Hồ sơ danh tính của :store cần chỉnh sửa'),
            $l('multi_vendor.mail.kyc_reviewed.title_approved', ':store — identity verified', ':store — đã xác minh danh tính'),
            $l('multi_vendor.mail.kyc_reviewed.title_rejected', ':store — identity profile rejected', ':store — hồ sơ danh tính bị từ chối'),
            $l('multi_vendor.mail.kyc_reviewed.intro_approved', 'The marketplace approved your identity profile. Your store now carries the verified badge.', 'Sàn đã duyệt hồ sơ danh tính của bạn. Gian hàng nay mang huy hiệu đã xác minh.'),
            $l('multi_vendor.mail.kyc_reviewed.intro_rejected', 'The marketplace could not approve your identity profile. Please review the reason and resubmit.', 'Sàn chưa thể duyệt hồ sơ danh tính của bạn. Vui lòng xem lý do và nộp lại.'),
            $l('multi_vendor.mail.kyc_reviewed.button', 'Open identity verification', 'Mở trang xác minh')
        ));
    }

    /**
     * i18n rows of order disputes (S3-3): flags, customer box, vendor panel, root desk, mail.
     */
    public static function seedDisputeLanguage(): void
    {
        $l = function (string $code, string $en, string $vi) {
            return [
                ['code' => $code, 'text' => $en, 'position' => 'multi_vendor', 'location' => 'en'],
                ['code' => $code, 'text' => $vi, 'position' => 'multi_vendor', 'location' => 'vi'],
            ];
        };
        Languages::insertOrIgnore(array_merge(
            $l('multi_vendor.MultiVendor_mail_dispute', 'Email on disputes', 'Email khi có khiếu nại'),
            $l('multi_vendor.MultiVendor_mail_dispute_help', 'Vendor + marketplace when a customer opens a dispute; customer when the vendor refuses; customer + vendor when it is decided.', 'Gửi vendor + sàn khi khách mở khiếu nại; gửi khách khi vendor từ chối; gửi khách + vendor khi có quyết định.'),
            $l('multi_vendor.MultiVendor_dispute_window_days', 'Dispute window (days after the order finished)', 'Cửa sổ khiếu nại (số ngày sau khi đơn hoàn tất)'),
            $l('multi_vendor.MultiVendor_dispute_window_days_help', 'A customer may open a dispute on a paid vendor order until this many days after it finished. Default 14.', 'Khách có thể mở khiếu nại trên đơn vendor đã thanh toán trong số ngày này sau khi đơn hoàn tất. Mặc định 14.'),
            $l('multi_vendor.MultiVendor_dispute_vendor_days', 'Days the vendor has to answer', 'Số ngày vendor phải phản hồi'),
            $l('multi_vendor.MultiVendor_dispute_vendor_days_help', 'An unanswered dispute escalates to the marketplace after this many days (checked when screens load — no cron needed). Default 3.', 'Khiếu nại không được phản hồi sẽ tự chuyển lên sàn sau số ngày này (kiểm khi mở màn — không cần cron). Mặc định 3.'),
            $l('multi_vendor.dispute.title', 'Dispute / refund request', 'Khiếu nại / yêu cầu hoàn tiền'),
            $l('multi_vendor.dispute.root_title', 'Disputes', 'Khiếu nại'),
            $l('multi_vendor.dispute.none', 'No disputes.', 'Chưa có khiếu nại.'),
            $l('multi_vendor.dispute.status', 'Status', 'Trạng thái'),
            $l('multi_vendor.dispute.status_open', 'Waiting for the seller', 'Chờ người bán'),
            $l('multi_vendor.dispute.status_escalated', 'With the marketplace', 'Sàn đang xử lý'),
            $l('multi_vendor.dispute.status_resolved', 'Decided', 'Đã quyết định'),
            $l('multi_vendor.dispute.status_withdrawn', 'Withdrawn', 'Đã rút'),
            $l('multi_vendor.dispute.type', 'Problem', 'Vấn đề'),
            $l('multi_vendor.dispute.type_not_received', 'Order not received', 'Không nhận được hàng'),
            $l('multi_vendor.dispute.type_damaged', 'Damaged / defective', 'Hàng hỏng / lỗi'),
            $l('multi_vendor.dispute.type_wrong_item', 'Wrong item', 'Giao sai hàng'),
            $l('multi_vendor.dispute.type_refund_request', 'Refund request', 'Yêu cầu hoàn tiền'),
            $l('multi_vendor.dispute.type_other', 'Other', 'Khác'),
            $l('multi_vendor.dispute.reason', 'Describe the problem', 'Mô tả vấn đề'),
            $l('multi_vendor.dispute.requested_amount', 'Refund requested', 'Số tiền yêu cầu hoàn'),
            $l('multi_vendor.dispute.refundable', 'Refundable', 'Có thể hoàn'),
            $l('multi_vendor.dispute.vendor_response', 'Seller response', 'Phản hồi của người bán'),
            $l('multi_vendor.dispute.vendor_deadline', 'The seller must answer before :date; otherwise the marketplace takes over.', 'Người bán phải phản hồi trước :date; nếu không, sàn sẽ tiếp nhận.'),
            $l('multi_vendor.dispute.escalated_hint', 'The marketplace is reviewing this dispute and will decide.', 'Sàn đang xem xét khiếu nại này và sẽ quyết định.'),
            $l('multi_vendor.dispute.resolution', 'Decision', 'Quyết định'),
            $l('multi_vendor.dispute.resolution_refund_full', 'Full refund', 'Hoàn toàn bộ'),
            $l('multi_vendor.dispute.resolution_refund_partial', 'Partial refund', 'Hoàn một phần'),
            $l('multi_vendor.dispute.resolution_reject', 'Rejected', 'Từ chối'),
            $l('multi_vendor.dispute.resolution_amount', 'Refund amount', 'Số tiền hoàn'),
            $l('multi_vendor.dispute.resolution_note', 'Note', 'Ghi chú'),
            $l('multi_vendor.dispute.refund_outside_hint', 'The refund is recorded on your order; the money is returned through your original payment method by the marketplace.', 'Khoản hoàn đã được ghi vào đơn; sàn trả tiền qua phương thức thanh toán ban đầu của bạn.'),
            $l('multi_vendor.dispute.open', 'Open a dispute', 'Gửi khiếu nại'),
            $l('multi_vendor.dispute.open_help', 'Tell the seller what went wrong. Up to :amount can be refunded on this order. The seller answers first; the marketplace steps in if needed.', 'Cho người bán biết vấn đề. Đơn này có thể hoàn tối đa :amount. Người bán phản hồi trước; sàn sẽ can thiệp nếu cần.'),
            $l('multi_vendor.dispute.withdraw', 'Withdraw the dispute', 'Rút khiếu nại'),
            $l('multi_vendor.dispute.opened', 'Your dispute was sent to the seller.', 'Khiếu nại của bạn đã được gửi tới người bán.'),
            $l('multi_vendor.dispute.withdrawn', 'Your dispute was withdrawn.', 'Bạn đã rút khiếu nại.'),
            $l('multi_vendor.dispute.not_live', 'This dispute is no longer open.', 'Khiếu nại này không còn mở.'),
            $l('multi_vendor.dispute.ineligible_disabled', 'Disputes are not available on this marketplace.', 'Sàn chưa mở tính năng khiếu nại.'),
            $l('multi_vendor.dispute.ineligible_not_owner', 'You can only dispute your own orders.', 'Bạn chỉ có thể khiếu nại đơn của mình.'),
            $l('multi_vendor.dispute.ineligible_not_vendor', 'Disputes apply to orders from marketplace sellers.', 'Khiếu nại áp dụng cho đơn của người bán trên sàn.'),
            $l('multi_vendor.dispute.ineligible_status', 'This order was canceled, failed or already refunded.', 'Đơn này đã bị hủy, thất bại hoặc đã hoàn tiền.'),
            $l('multi_vendor.dispute.ineligible_nothing_paid', 'Nothing has been paid on this order yet.', 'Đơn này chưa có khoản thanh toán nào.'),
            $l('multi_vendor.dispute.ineligible_window', 'The dispute window for this order has closed.', 'Đã hết thời hạn khiếu nại cho đơn này.'),
            $l('multi_vendor.dispute.ineligible_exists', 'A dispute is already in progress on this order.', 'Đơn này đang có khiếu nại đang xử lý.'),
            $l('multi_vendor.dispute.ineligible_type', 'Please choose a valid problem type.', 'Vui lòng chọn loại vấn đề hợp lệ.'),
            $l('multi_vendor.dispute.ineligible_reason', 'Please describe the problem in at least 10 characters.', 'Vui lòng mô tả vấn đề ít nhất 10 ký tự.'),
            $l('multi_vendor.dispute.refund_amount', 'Refund amount', 'Số tiền hoàn'),
            $l('multi_vendor.dispute.vendor_note', 'Your message to the customer', 'Lời nhắn cho khách'),
            $l('multi_vendor.dispute.vendor_accept', 'Accept & refund', 'Chấp nhận & hoàn tiền'),
            $l('multi_vendor.dispute.vendor_reject', 'Refuse (marketplace decides)', 'Từ chối (sàn quyết định)'),
            $l('multi_vendor.dispute.vendor_accepted', 'Refund booked on the order; the customer was notified.', 'Đã ghi hoàn tiền vào đơn; khách đã được thông báo.'),
            $l('multi_vendor.dispute.vendor_rejected', 'Escalated to the marketplace.', 'Đã chuyển lên sàn xử lý.'),
            $l('multi_vendor.dispute.deadline', 'Seller deadline', 'Hạn người bán'),
            $l('multi_vendor.dispute.resolve', 'Decide', 'Quyết định'),
            $l('multi_vendor.dispute.resolve_title', 'Decide this dispute', 'Quyết định khiếu nại'),
            $l('multi_vendor.dispute.resolve_help', 'A refund is booked on the order ledger immediately (a full refund marks the order Refunded) and the seller\'s share is clawed back from the next payout. Return the money to the customer through the original payment method.', 'Khoản hoàn được ghi ngay vào sổ đơn (hoàn toàn bộ ⇒ đơn chuyển Đã hoàn tiền) và phần của người bán được thu hồi ở kỳ thanh toán kế tiếp. Hãy trả tiền cho khách qua phương thức thanh toán ban đầu.'),
            $l('multi_vendor.dispute.resolved', 'Dispute decided.', 'Đã quyết định khiếu nại.'),
            $l('multi_vendor.dispute.resolve_failed', 'Could not apply this decision (amount above the refundable balance, missing note, or the dispute is no longer open).', 'Không áp dụng được quyết định (số tiền vượt mức có thể hoàn, thiếu ghi chú, hoặc khiếu nại không còn mở).'),
            $l('multi_vendor.mail.dispute_opened.subject', 'Dispute opened on order #:order', 'Khiếu nại mới cho đơn #:order'),
            $l('multi_vendor.mail.dispute_opened.title', 'Order #:order — dispute opened', 'Đơn #:order — có khiếu nại'),
            $l('multi_vendor.mail.dispute_opened.intro', 'A customer opened a dispute on an order of :store.', 'Khách hàng vừa mở khiếu nại cho một đơn của :store.'),
            $l('multi_vendor.mail.dispute_opened.deadline', 'The seller should answer before :date; otherwise the marketplace decides.', 'Người bán nên phản hồi trước :date; nếu không, sàn sẽ quyết định.'),
            $l('multi_vendor.mail.dispute_opened.button', 'Open the order', 'Mở đơn hàng'),
            $l('multi_vendor.mail.dispute_vendor_responded.subject', 'Order #:order — the seller answered your dispute', 'Đơn #:order — người bán đã phản hồi khiếu nại'),
            $l('multi_vendor.mail.dispute_vendor_responded.title', 'Order #:order — seller response', 'Đơn #:order — phản hồi của người bán'),
            $l('multi_vendor.mail.dispute_vendor_responded.intro', ':store did not accept your dispute.', ':store chưa chấp nhận khiếu nại của bạn.'),
            $l('multi_vendor.mail.dispute_vendor_responded.next', 'The marketplace will now review the case and decide.', 'Sàn sẽ xem xét và đưa ra quyết định.'),
            $l('multi_vendor.mail.dispute_resolved.subject', 'Order #:order — dispute decided', 'Đơn #:order — khiếu nại đã có quyết định'),
            $l('multi_vendor.mail.dispute_resolved.title', 'Order #:order — decision', 'Đơn #:order — quyết định'),
            $l('multi_vendor.mail.dispute_resolved.intro', 'The dispute on your order from :store has been decided.', 'Khiếu nại cho đơn của bạn tại :store đã có quyết định.'),
            $l('multi_vendor.mail.dispute_resolved.refund_hint', 'The refund is recorded on the order and returned through the original payment method.', 'Khoản hoàn đã được ghi vào đơn và trả qua phương thức thanh toán ban đầu.')
        ));
    }

    /**
     * i18n rows of vendor plans (S3-4): root screen, store config select, vendor card, ledger kind.
     */
    public static function seedPlanLanguage(): void
    {
        $l = function (string $code, string $en, string $vi) {
            return [
                ['code' => $code, 'text' => $en, 'position' => 'multi_vendor', 'location' => 'en'],
                ['code' => $code, 'text' => $vi, 'position' => 'multi_vendor', 'location' => 'vi'],
            ];
        };
        Languages::insertOrIgnore(array_merge(
            $l('multi_vendor.clawback.kind_fee', 'Plan fee', 'Phí gói'),
            $l('multi_vendor.plan.root_title', 'Vendor plans', 'Gói vendor'),
            $l('multi_vendor.plan.help', 'A plan caps the number of products, may set its own marketplace commission (marketplace rate now :rate%; a per-store override still wins) and may charge a period fee that is netted into the vendor\'s next payout. Assign a plan on the store configuration screen.', 'Gói giới hạn số sản phẩm, có thể đặt hoa hồng riêng (tỷ lệ sàn hiện :rate%; hoa hồng riêng theo gian hàng vẫn ưu tiên) và có thể thu phí theo kỳ — phí được bù trừ vào kỳ thanh toán kế tiếp của vendor. Gán gói ở màn cấu hình gian hàng.'),
            $l('multi_vendor.plan.none', 'No plans yet.', 'Chưa có gói.'),
            $l('multi_vendor.plan.create', 'New plan', 'Thêm gói'),
            $l('multi_vendor.plan.edit', 'Edit plan', 'Sửa gói'),
            $l('multi_vendor.plan.plan', 'Plan', 'Gói'),
            $l('multi_vendor.plan.code', 'Code', 'Mã'),
            $l('multi_vendor.plan.name', 'Name', 'Tên gói'),
            $l('multi_vendor.plan.description', 'Description', 'Mô tả'),
            $l('multi_vendor.plan.max_products', 'Product cap', 'Giới hạn sản phẩm'),
            $l('multi_vendor.plan.empty_unlimited', 'empty = unlimited', 'để trống = không giới hạn'),
            $l('multi_vendor.plan.unlimited', 'Unlimited', 'Không giới hạn'),
            $l('multi_vendor.plan.commission_rate', 'Plan commission', 'Hoa hồng theo gói'),
            $l('multi_vendor.plan.empty_marketplace', 'empty = marketplace :rate%', 'để trống = theo sàn :rate%'),
            $l('multi_vendor.plan.marketplace_rate', 'Marketplace (:rate%)', 'Theo sàn (:rate%)'),
            $l('multi_vendor.plan.fee', 'Fee', 'Phí'),
            $l('multi_vendor.plan.fee_amount', 'Fee amount', 'Số tiền phí'),
            $l('multi_vendor.plan.fee_currency', 'Fee currency', 'Loại tiền phí'),
            $l('multi_vendor.plan.fee_amount_hint', 'Charged once per period in the currency below and deducted from the store\'s next payout. 0 = free plan.', 'Thu một lần mỗi kỳ theo loại tiền bên dưới, trừ vào kỳ chi trả kế tiếp của gian hàng. 0 = gói miễn phí.'),
            $l('multi_vendor.plan.default_hint', 'The plan every store is on when it has no plan of its own or its period has ended. Only one plan is default.', 'Gói áp cho mọi gian hàng chưa được gán gói hoặc đã hết kỳ. Chỉ có một gói mặc định.'),
            $l('multi_vendor.plan.fee_period', 'Fee period', 'Kỳ phí'),
            $l('multi_vendor.plan.fee_needs_period', 'A fee needs a period (month or year).', 'Có phí thì phải chọn kỳ (tháng hoặc năm).'),
            $l('multi_vendor.plan.period_none', 'No cycle', 'Không theo kỳ'),
            $l('multi_vendor.plan.period_month', 'Month', 'Tháng'),
            $l('multi_vendor.plan.period_year', 'Year', 'Năm'),
            $l('multi_vendor.plan.default', 'Default', 'Mặc định'),
            $l('multi_vendor.plan.make_default', 'Make default', 'Đặt mặc định'),
            $l('multi_vendor.plan.default_option', 'Default plan (:name)', 'Gói mặc định (:name)'),
            $l('multi_vendor.plan.stores', 'Stores', 'Gian hàng'),
            $l('multi_vendor.plan.delete_in_use', 'This plan has subscriptions and cannot be deleted.', 'Gói này đã có gian hàng đăng ký, không xoá được.'),
            $l('multi_vendor.plan.subscriptions', 'Stores on a plan', 'Gian hàng theo gói'),
            $l('multi_vendor.plan.subscriptions_help', 'Periods do not renew by themselves: when a period ends the store falls back to the default plan until you renew it here (a new period books a new fee).', 'Kỳ gói không tự gia hạn: hết kỳ, gian hàng về gói mặc định cho tới khi bạn gia hạn ở đây (kỳ mới ghi một khoản phí mới).'),
            $l('multi_vendor.plan.no_subscriptions', 'No store is on a plan.', 'Chưa có gian hàng nào theo gói.'),
            $l('multi_vendor.plan.period', 'Period', 'Kỳ'),
            $l('multi_vendor.plan.expiring_soon', 'Expiring soon', 'Sắp hết hạn'),
            $l('multi_vendor.plan.renew', 'Renew', 'Gia hạn'),
            $l('multi_vendor.plan.renewed', 'Renewed — a new period started and its fee was booked.', 'Đã gia hạn — kỳ mới bắt đầu và phí đã được ghi.'),
            $l('multi_vendor.plan.renew_failed', 'Nothing to renew for this store.', 'Gian hàng này không có gói để gia hạn.'),
            $l('multi_vendor.plan.cancel', 'Cancel', 'Huỷ gói'),
            $l('multi_vendor.plan.cancel_confirm', 'Take this store off its plan? It falls back to the default plan immediately.', 'Huỷ gói của gian hàng này? Gian hàng về gói mặc định ngay.'),
            $l('multi_vendor.plan.canceled', 'The store is back on the default plan.', 'Gian hàng đã về gói mặc định.'),
            $l('multi_vendor.plan.assign_help', 'Choosing a plan starts a new period now and books its fee into the payout ledger.', 'Chọn gói sẽ bắt đầu kỳ mới ngay và ghi phí vào sổ thanh toán.'),
            $l('multi_vendor.plan.current_period', 'Current period: :start → :end', 'Kỳ hiện tại: :start → :end'),
            $l('multi_vendor.plan.products_used', ':count / :max products', ':count / :max sản phẩm'),
            $l('multi_vendor.plan.valid_until', 'Valid until :date', 'Hiệu lực đến :date'),
            $l('multi_vendor.plan.expired_notice', 'Your plan period ended — you are on the default plan until the marketplace renews it.', 'Kỳ gói đã hết — bạn đang ở gói mặc định cho tới khi sàn gia hạn.'),
            $l('multi_vendor.plan.limit_reached', 'Product cap of your plan reached (:max). Contact the marketplace to upgrade.', 'Đã đạt giới hạn sản phẩm của gói (:max). Liên hệ sàn để nâng gói.')
        ));
    }

    public static function seedTierLanguage(): void
    {
        $l = function (string $code, string $en, string $vi) {
            return [
                ['code' => $code, 'text' => $en, 'position' => 'multi_vendor', 'location' => 'en'],
                ['code' => $code, 'text' => $vi, 'position' => 'multi_vendor', 'location' => 'vi'],
            ];
        };
        Languages::insertOrIgnore(array_merge(
            $l('multi_vendor.tier.pro_only', ':feature is available in MultiVendorPro.', ':feature có trong bản MultiVendorPro.'),
            $l('multi_vendor.tier.quota_reached', 'The Free edition allows up to :quota vendor stores. Install MultiVendorPro to add more.', 'Bản Free cho tối đa :quota gian hàng. Cài MultiVendorPro để thêm.'),
            $l('multi_vendor.tier.quota_reached_register', 'This marketplace is not accepting new vendors right now.', 'Sàn hiện không nhận thêm vendor mới.'),
            $l('multi_vendor.tier.edition', 'Edition', 'Phiên bản')
        ));
    }

    /**
     * Add the "Review" sidebar item under the marketplace block when missing
     * (install() only seeds children while creating the block). Idempotent on uri.
     */
    /**
     * Seed (and keep) the marketplace's Pro menu rows. The Free plugin owns
     * every row under its own menu block — including the Pro screens, whose URI
     * points at the gateway rather than the real screen. That way a Free
     * marketplace still sees what Pro offers, installing Pro needs no menu
     * change (the gateway redirects), and there is exactly one plugin writing
     * rows under this block (two writers is how duplicate items happen).
     *
     * Re-entrant: rows left by an older install that pointed straight at the
     * real screen are converted to the gateway URI instead of being duplicated.
     *
     * @return void
     */
    /**
     * Language rows for the upgrade funnel (US-multi-vendor-pro-upgrade-funnel):
     * the gateway page, the inline hints, the menu labels, and one name +
     * one-line description for every Pro feature — including the ones that are
     * not a screen, so a Free marketplace learns they exist at all.
     *
     * Two voices on purpose: `blurb_root` speaks to the marketplace owner (who
     * buys), `blurb_vendor` to a shop (who asks the marketplace).
     *
     * @return void
     */
    public static function seedProFunnelLanguage(): void
    {
        $l = function (string $code, string $en, string $vi) {
            return [
                ['code' => $code, 'text' => $en, 'position' => 'multi_vendor', 'location' => 'en'],
                ['code' => $code, 'text' => $vi, 'position' => 'multi_vendor', 'location' => 'vi'],
            ];
        };
        Languages::insertOrIgnore(array_merge(
            $l('multi_vendor.pro.heading', 'MultiVendorPro feature', 'Tính năng bản MultiVendorPro'),
            $l('multi_vendor.pro.blurb_root', 'This screen is part of MultiVendorPro. Upgrade to unlock it together with everything below.', 'Màn hình này thuộc bản MultiVendorPro. Nâng cấp để mở khoá cùng toàn bộ danh sách bên dưới.'),
            $l('multi_vendor.pro.blurb_vendor', 'This feature is part of the Pro edition of the marketplace you sell on.', 'Tính năng này thuộc bản Pro của sàn mà bạn đang bán hàng.'),
            $l('multi_vendor.pro.vendor_note', 'Ask the marketplace to upgrade if you need it.', 'Nếu bạn cần, hãy đề nghị sàn nâng cấp.'),
            $l('multi_vendor.pro.cta', 'Upgrade to Pro', 'Nâng cấp Pro'),
            $l('multi_vendor.pro.buy_url', 'https://gp247.net/en/product/multi-vendor-pro.html', 'https://gp247.net/vi/product/multi-vendor-pro.html'),
            $l('multi_vendor.pro.list_title', 'What Pro adds', 'Bản Pro có gì'),
            $l('multi_vendor.pro.included', 'Included', 'Đang có'),
            $l('multi_vendor.pro.locked', 'Pro', 'Pro'),
            $l('multi_vendor.pro.learn_more', 'See what Pro adds', 'Xem bản Pro có gì'),
            $l('multi_vendor.pro.setting_locked', 'Pro · :feature', 'Pro · :feature'),
            $l('multi_vendor.pro.menu.report', 'Reports (Pro)', 'Báo cáo (Pro)'),
            $l('multi_vendor.pro.menu.commission', 'Commission report (Pro)', 'Báo cáo hoa hồng (Pro)'),
            $l('multi_vendor.pro.menu.review', 'Approval queue (Pro)', 'Hàng chờ duyệt (Pro)'),
            $l('multi_vendor.pro.menu.dispute', 'Complaints (Pro)', 'Khiếu nại (Pro)'),
            $l('multi_vendor.pro.menu.plan', 'Shop plans (Pro)', 'Gói gian hàng (Pro)'),
            // Titles of paid VENDOR screens: the free sidebar and the gateway show
            // them on a Free marketplace too, so they cannot depend on the paid
            // plugin's seed (its rows may never have been inserted).
            $l('multi_vendor.reviews.title', 'Reviews', 'Đánh giá'),
            $l('multi_vendor.groups.title', 'Price groups', 'Nhóm giá'),
            $l('multi_vendor.order_create.title', 'New order', 'Tạo đơn hàng'),
            $l('multi_vendor.vendor_plugins.title', 'Store plugins', 'Plugin của gian hàng'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::titleKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_PER_VENDOR_COMMISSION), 'Commission per shop', 'Hoa hồng riêng từng gian hàng'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_PER_VENDOR_COMMISSION), 'Set a different commission rate for one shop instead of the marketplace rate.', 'Đặt tỷ lệ hoa hồng riêng cho một gian hàng thay vì dùng tỷ lệ chung của sàn.'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::titleKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_QUICK_ORDER), 'Wholesale quick order', 'Đặt hàng nhanh (bán sỉ)'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_QUICK_ORDER), 'A one-page order form on the shop page for buyers who order many lines at once.', 'Trang đặt hàng một lần nhiều dòng trên trang gian hàng, dành cho khách mua số lượng.'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::titleKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_REPORTS), 'Marketplace reports', 'Báo cáo sàn'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_REPORTS), 'Revenue, orders and commission per shop and per period, with Excel export.', 'Doanh thu, đơn hàng và hoa hồng theo gian hàng và theo kỳ, xuất được Excel.'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::titleKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_ORDER_FULFILLMENT), 'Full order handling', 'Xử lý đơn đầy đủ'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_ORDER_FULFILLMENT), 'Shops move an order through every status, not only the shipping step.', 'Gian hàng chuyển đơn qua mọi trạng thái, không chỉ mỗi bước giao hàng.'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::titleKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_MODERATION_QUEUE), 'Approval queue', 'Hàng chờ duyệt'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_MODERATION_QUEUE), 'Approve new shops and their products before they appear on the marketplace.', 'Duyệt gian hàng mới và sản phẩm của họ trước khi lên sàn.'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::titleKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_PAYOUT_ACCOUNT), 'Payout account', 'Tài khoản nhận tiền'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_PAYOUT_ACCOUNT), 'Each shop records where it wants to be paid; the marketplace pays from that.', 'Mỗi gian hàng khai nơi nhận tiền, sàn chi trả theo đó.'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::titleKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_PAYOUT_STATEMENT), 'Payout statement', 'Sao kê thanh toán'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_PAYOUT_STATEMENT), 'Download the payout ledger of a period as a file, for both sides to check.', 'Tải sổ thanh toán của một kỳ ra file để hai bên đối soát.'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::titleKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_MAIL_PENDING_REVIEW), 'Mail: waiting for approval', 'Thư: đang chờ duyệt'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_MAIL_PENDING_REVIEW), 'The marketplace is told when a shop or a product is waiting for approval.', 'Báo cho sàn khi có gian hàng hoặc sản phẩm đang chờ duyệt.'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::titleKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_MAIL_VENDOR_APPROVED), 'Mail: shop approved', 'Thư: đã duyệt gian hàng'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_MAIL_VENDOR_APPROVED), 'The shop is told the moment the marketplace approves it.', 'Báo cho gian hàng ngay khi sàn duyệt.'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::titleKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_MAIL_PAYOUT_DONE), 'Mail: payment sent', 'Thư: đã chi trả'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_MAIL_PAYOUT_DONE), 'The shop is told when a payout has been made, with the amount and period.', 'Báo cho gian hàng khi đã chi trả, kèm số tiền và kỳ.'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::titleKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_VENDOR_PLUGINS), 'Shop-level plugin settings', 'Gian hàng tự cấu hình plugin'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_VENDOR_PLUGINS), 'Shops configure the plugins the marketplace allows, each for its own shop.', 'Gian hàng tự cấu hình các plugin sàn cho phép, trong phạm vi gian hàng mình.'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::titleKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_CLAWBACK), 'Automatic adjustments', 'Điều chỉnh tự động'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_CLAWBACK), 'When an order is refunded or cancelled, the commission already booked is reversed.', 'Khi đơn bị hoàn hoặc huỷ, phần hoa hồng đã ghi sổ được điều chỉnh ngược lại.'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::titleKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_KYC), 'Shop verification (KYC)', 'Xác minh gian hàng (KYC)'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_KYC), 'Collect and check the shop identity papers; a verified badge and payout gates.', 'Thu thập và kiểm tra giấy tờ gian hàng; có dấu đã xác minh và chặn chi trả khi chưa xong.'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::titleKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_DISPUTE), 'Complaint desk', 'Bàn khiếu nại'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_DISPUTE), 'Customer complaints the shop did not settle come to the marketplace to decide.', 'Khiếu nại khách hàng mà gian hàng chưa giải quyết sẽ chuyển lên sàn quyết định.'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::titleKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_VENDOR_PLANS), 'Shop plans', 'Gói gian hàng'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_VENDOR_PLANS), 'Sell plans with a product cap, a plan commission rate and a period fee.', 'Bán gói có trần sản phẩm, tỷ lệ hoa hồng riêng và phí theo kỳ.'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::titleKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_VENDOR_REVIEWS), 'Shop answers reviews', 'Gian hàng trả lời đánh giá'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_VENDOR_REVIEWS), 'Shops read and publicly answer the reviews of their own shop.', 'Gian hàng đọc và trả lời công khai đánh giá của chính gian hàng mình.'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::titleKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_PAYOUT_BATCH), 'Pay a whole batch', 'Chi trả theo lô'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_PAYOUT_BATCH), 'One bank instruction file for many shops, then record the whole batch as paid.', 'Một file lệnh chi cho nhiều gian hàng, rồi ghi nhận cả lô đã trả.'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::titleKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_CUSTOMER_GROUP_PRICING), 'Dealer pricing', 'Giá theo nhóm khách'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_CUSTOMER_GROUP_PRICING), 'Each shop groups its own dealers and gives each group its agreed discount.', 'Mỗi gian hàng tự nhóm đại lý của mình và cho mỗi nhóm mức chiết khấu đã thoả thuận.'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::titleKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_VENDOR_ORDER_CREATE), 'Shop creates an order', 'Gian hàng tạo đơn'),
            $l(\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::descKey(\App\GP247\Plugins\MultiVendor\Tier\Tier::F_VENDOR_ORDER_CREATE), 'Shop staff type an order for a customer, prices filled in from that customer group.', 'Nhân viên gian hàng tạo đơn cho khách, giá điền sẵn theo nhóm của khách đó.')
        ));
    }

    /**
     * Insert the settings rows that are missing, and repair the metadata of the
     * ones that already exist.
     *
     * WHY the repair half: `admin_config` is unique on (key, store_id), so a row
     * left behind by something else — a Feature-Test whose transaction was cut
     * short by a DDL statement, an older plugin version, a half-finished
     * uninstall — makes insertOrIgnore skip the real row without a word. The
     * settings screen then renders the raw key (`MultiVendor_kyc_required`)
     * instead of its label, because the stray row carries no language code, and
     * the feature reads a row nobody seeded. Repairing on every convergence is
     * how a site heals itself.
     *
     * `value` is never touched: it belongs to the site owner. Only the fields
     * that say what the row IS (group, code, detail, sort) are converged.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return bool True when the insert reported success (install() reads it).
     */
    private static function seedConfigRows(array $rows): bool
    {
        $inserted = (bool) AdminConfig::insertOrIgnore($rows);

        foreach ($rows as $row) {
            AdminConfig::where('key', $row['key'])
                ->where('store_id', $row['store_id'])
                ->where(function ($query) use ($row) {
                    $query->where('code', '<>', $row['code'])
                        ->orWhere('detail', '<>', $row['detail'])
                        ->orWhere('group', '<>', $row['group']);
                })
                ->update([
                    'group'  => $row['group'],
                    'code'   => $row['code'],
                    'detail' => $row['detail'],
                    'sort'   => $row['sort'],
                ]);
        }

        return $inserted;
    }

    /**
     * The one convergence list of this plugin — every step re-entrant, every
     * lifecycle hook goes through here.
     *
     * WHY it exists: install() and update() used to keep their own step lists,
     * and the upgrade funnel (mod 20260914T070720) landed in update() only. The
     * core installer calls install(), so a site that uninstalled (data only) and
     * installed again silently lost the five Pro menu items — a dev machine
     * never sees it, having been through update() long ago
     * (RISK-TECH-mv-install-update-drift). Adding a seeding step now has exactly
     * one place to add it to.
     *
     * @return void
     */
    public static function converge(): void
    {
        self::ensureProMenus();
        self::seedProFunnelLanguage();
        self::seedTrustSignalLanguage();
        self::seedPlanLanguage();
        self::seedPlanSelfServiceLanguage();

        event(self::SEED_EVENT);
    }

    /**
     * Repair path for a site that is already broken: the marketplace settings
     * screen calls this on mount, so the owner never needs the CLI.
     *
     * Guarded by one count query — mount() runs on every render of that screen
     * and a healthy site must not be rewritten each time.
     *
     * @return void
     */
    public static function repairIfNeeded(): void
    {
        $expected = 0;
        foreach (\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::SCREENS as $screen) {
            if ($screen['audience'] === \App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::ROOT) {
                $expected++;
            }
        }
        if (AdminMenu::where('uri', 'like', 'admin::MultiVendor/pro/%')->count() >= $expected) {
            return;
        }

        self::converge();
    }

    /**
     * Drop the marketplace menu block and its items. Tolerates a block that is
     * already gone: uninstalling twice (or after a half-finished uninstall) used
     * to throw on a null block, and the catch turned a missing menu into
     * "uninstall failed", hiding what actually happened.
     *
     * @return void
     */
    public static function removeMenuBlock(): void
    {
        $block = AdminMenu::where('key', 'ADMIN_MVENDOR_SETTING')->first();
        if ($block === null) {
            return;
        }
        AdminMenu::where('parent_id', $block->id)->delete();
        AdminMenu::where('id', $block->id)->delete();
    }

    public static function ensureProMenus(): void
    {
        // The block holds Free and Pro items alike, so the edition label belongs
        // on the Pro items only (`multi_vendor.pro.menu.*`), never on the block.
        // Sites installed before this ran carry the old title — converge it.
        foreach (['en' => 'MARKETPLACE', 'vi' => 'CHỢ BÁN HÀNG'] as $locale => $title) {
            Languages::where('code', 'multi_vendor.plugin_block')->where('location', $locale)
                ->where('text', 'like', '%Pro%')
                ->update(['text' => $title]);
        }

        $block = AdminMenu::where('key', 'ADMIN_MVENDOR_SETTING')->first();
        if ($block === null) {
            return;
        }
        $sort = 5;
        foreach (\App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::SCREENS as $slug => $screen) {
            if ($screen['audience'] !== \App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue::ROOT) {
                continue;
            }
            $uri = 'admin::MultiVendor/pro/'.$slug;
            $sort++;
            // A row from an older install (or from the Pro plugin, which used to
            // seed these) points straight at the real screen. It must go in BOTH
            // cases, otherwise the sidebar shows the same screen twice: convert it
            // when the gateway row is missing, delete it when the gateway row is
            // already there. Leaving it alone was the duplicate this whole change
            // set out to prevent (RISK-TECH-mv-teaser-menu-duplicates).
            $legacyUri = 'admin::MultiVendor/'.$slug;
            if (AdminMenu::where('uri', $uri)->exists()) {
                AdminMenu::where('uri', $legacyUri)->delete();
                continue;
            }
            $legacy = AdminMenu::where('uri', $legacyUri)->first();
            if ($legacy !== null) {
                $legacy->update(['uri' => $uri, 'title' => $screen['title'], 'icon' => $screen['icon']]);
                continue;
            }
            AdminMenu::insert([
                'parent_id' => $block->id,
                'sort'      => $sort,
                'title'     => $screen['title'],
                'icon'      => $screen['icon'],
                'uri'       => $uri,
            ]);
        }
    }

    /**
     * Language rows for the moderation queue + review mails (S1-4). Called from
     * seedNotificationSettings() (single entry point).
     */
    /**
     * Language rows for payout account / statement (S1-5). Called from
     * seedNotificationSettings() (single entry point).
     */
    public static function seedPayoutLanguage(): void
    {
        $l = function (string $code, string $en, string $vi) {
            return [
                ['code' => $code, 'text' => $en, 'position' => 'multi_vendor', 'location' => 'en'],
                ['code' => $code, 'text' => $vi, 'position' => 'multi_vendor', 'location' => 'vi'],
            ];
        };
        Languages::insertOrIgnore(array_merge(
            $l('multi_vendor.payout.title', 'Payout account', 'Thông tin nhận tiền'),
            $l('multi_vendor.payout.help', 'Tell the marketplace where to send your payouts. The account number is stored encrypted and only its last digits are shown.', 'Cho sàn biết nơi chuyển tiền cho bạn. Số tài khoản được mã hoá khi lưu và chỉ hiện vài số cuối.'),
            $l('multi_vendor.payout.method', 'Method', 'Phương thức'),
            $l('multi_vendor.payout.method_bank_transfer', 'Bank transfer', 'Chuyển khoản ngân hàng'),
            $l('multi_vendor.payout.method_paypal', 'PayPal', 'PayPal'),
            $l('multi_vendor.payout.method_other', 'Other', 'Khác'),
            $l('multi_vendor.payout.bank_name', 'Bank / provider', 'Ngân hàng / nhà cung cấp'),
            $l('multi_vendor.payout.account_name', 'Account holder', 'Tên chủ tài khoản'),
            $l('multi_vendor.payout.account_number', 'Account number / PayPal e-mail', 'Số tài khoản / email PayPal'),
            $l('multi_vendor.payout.account_number_help', 'Leave empty to keep the stored value.', 'Để trống để giữ giá trị đã lưu.'),
            $l('multi_vendor.payout.note', 'Note', 'Ghi chú'),
            $l('multi_vendor.payout.saved', 'Payout account saved.', 'Đã lưu thông tin nhận tiền.'),
            $l('multi_vendor.payout.reference', 'Transaction reference', 'Mã giao dịch'),
            $l('multi_vendor.payout.column', 'Payout to / reference', 'Chuyển tới / mã giao dịch'),
            $l('multi_vendor.payout.export', 'Export statement', 'Xuất bảng kê'),
            $l('multi_vendor.payout.from', 'From', 'Từ ngày'),
            $l('multi_vendor.payout.to', 'To', 'Đến ngày'),
            $l('multi_vendor.payout.statement', 'Payout statement', 'Bảng kê thanh toán'),
            $l('multi_vendor.payout.vendor_share', 'Vendor share (%)', 'Tỷ lệ vendor nhận (%)'),
            $l('multi_vendor.payout.paid_by', 'Paid by', 'Người đánh dấu đã trả')
        ));
    }

    public static function seedReviewLanguage(): void
    {
        $l = function (string $code, string $en, string $vi) {
            return [
                ['code' => $code, 'text' => $en, 'position' => 'multi_vendor', 'location' => 'en'],
                ['code' => $code, 'text' => $vi, 'position' => 'multi_vendor', 'location' => 'vi'],
            ];
        };
        Languages::insertOrIgnore(array_merge(
            $l('multi_vendor.vendor_review', 'Review queue', 'Kiểm duyệt'),
            $l('multi_vendor.review.title', 'Moderation queue', 'Hàng chờ kiểm duyệt'),
            $l('multi_vendor.review.tab_vendors', 'Vendors waiting', 'Vendor chờ duyệt'),
            $l('multi_vendor.review.tab_products', 'Products waiting', 'Sản phẩm chờ duyệt'),
            $l('multi_vendor.review.none_pending', 'Nothing waiting for review.', 'Không có mục nào chờ duyệt.'),
            $l('multi_vendor.review.last_rejection', 'Last rejection', 'Lần từ chối gần nhất'),
            $l('multi_vendor.review.approve', 'Approve', 'Duyệt'),
            $l('multi_vendor.review.reject', 'Reject', 'Từ chối'),
            $l('multi_vendor.review.reject_title', 'Reject with a reason', 'Từ chối kèm lý do'),
            $l('multi_vendor.review.reason', 'Reason', 'Lý do'),
            $l('multi_vendor.review.reason_help', 'At least 5 characters. The vendor receives this reason by e-mail.', 'Ít nhất 5 ký tự. Vendor nhận lý do này qua email.'),
            $l('multi_vendor.review.approved', 'Approved.', 'Đã duyệt.'),
            $l('multi_vendor.review.rejected', 'Rejected — the vendor has been notified.', 'Đã từ chối — vendor đã được thông báo.'),
            $l('multi_vendor.review.not_pending', 'This item is no longer waiting for review (or the reason is too short).', 'Mục này không còn chờ duyệt (hoặc lý do quá ngắn).'),
            $l('multi_vendor.mail.vendor_rejected.subject', 'Your store :store was not approved', 'Gian hàng :store chưa được duyệt'),
            $l('multi_vendor.mail.vendor_rejected.title', ':store — not approved yet', ':store — chưa được duyệt'),
            $l('multi_vendor.mail.vendor_rejected.intro', 'The marketplace reviewed your store registration and could not approve it yet.', 'Sàn đã xem đăng ký gian hàng của bạn và chưa thể duyệt.'),
            $l('multi_vendor.mail.vendor_rejected.next', 'Please update your store information and contact the marketplace if you have questions.', 'Vui lòng cập nhật thông tin gian hàng và liên hệ sàn nếu có thắc mắc.'),
            $l('multi_vendor.mail.product_reviewed.subject_approved', 'Product approved: :product', 'Sản phẩm đã được duyệt: :product'),
            $l('multi_vendor.mail.product_reviewed.subject_rejected', 'Product not approved: :product', 'Sản phẩm chưa được duyệt: :product'),
            $l('multi_vendor.mail.product_reviewed.title_approved', ':product is now live', ':product đã lên sàn'),
            $l('multi_vendor.mail.product_reviewed.title_rejected', ':product was not approved', ':product chưa được duyệt'),
            $l('multi_vendor.mail.product_reviewed.intro_approved', 'The marketplace approved your product. It is now visible to customers.', 'Sàn đã duyệt sản phẩm của bạn. Sản phẩm đã hiển thị với khách.'),
            $l('multi_vendor.mail.product_reviewed.intro_rejected', 'The marketplace reviewed your product and did not approve it. Edit it and save again to resubmit.', 'Sàn đã xem sản phẩm của bạn và chưa duyệt. Sửa rồi lưu lại để gửi duyệt lần nữa.'),
            $l('multi_vendor.mail.product_reviewed.button', 'Open my products', 'Mở danh sách sản phẩm')
        ));
    }

    public static function seedOrderFulfillmentLanguage(): void
    {
        $l = function (string $code, string $en, string $vi) {
            return [
                ['code' => $code, 'text' => $en, 'position' => 'multi_vendor', 'location' => 'en'],
                ['code' => $code, 'text' => $vi, 'position' => 'multi_vendor', 'location' => 'vi'],
            ];
        };
        Languages::insertOrIgnore(array_merge(
            $l('multi_vendor.MultiVendor_vendor_order_scope', 'Vendor order actions', 'Vendor được làm gì với đơn'),
            $l('multi_vendor.MultiVendor_vendor_order_scope_help', 'How far a vendor may move their own orders. Cancel, refund and re-opening always stay with the marketplace. "Complete" lets vendors mark orders Done, which puts them into the next payout period.', 'Vendor được chuyển đơn của mình tới đâu. Hủy, hoàn tiền và mở lại đơn luôn thuộc sàn. "Hoàn tất" cho vendor đánh dấu Hoàn thành — đơn sẽ vào kỳ thanh toán kế tiếp.'),
            $l('multi_vendor.vendor_order_scope_shipping', 'Shipping status only', 'Chỉ trạng thái giao hàng'),
            $l('multi_vendor.vendor_order_scope_confirm', 'Confirm + shipping (New/Hold → Processing)', 'Xác nhận + giao hàng (Mới/Giữ → Đang xử lý)'),
            $l('multi_vendor.vendor_order_scope_complete', 'Confirm + shipping + complete (Processing → Done)', 'Xác nhận + giao hàng + hoàn tất (Đang xử lý → Hoàn thành)'),
            $l('multi_vendor.order.transition_not_allowed', 'You may not move this order to that status.', 'Bạn không được chuyển đơn sang trạng thái này.'),
            $l('multi_vendor.order.status_locked', 'This order is finalized by the marketplace and can no longer be changed here.', 'Đơn đã được sàn chốt, không thể thay đổi ở đây.'),
            $l('multi_vendor.order.print_slip', 'Print packing slip', 'In phiếu giao'),
            $l('multi_vendor.order.history_status', 'Vendor :email changed status :from → :to', 'Vendor :email đổi trạng thái :from → :to'),
            $l('multi_vendor.order.history_shipping', 'Vendor :email changed shipping status :from → :to', 'Vendor :email đổi trạng thái giao hàng :from → :to'),
            $l('multi_vendor.order.history_shipment', 'Vendor :email updated shipment: :carrier :code', 'Vendor :email cập nhật vận đơn: :carrier :code'),
            $l('multi_vendor.shipment.title', 'Shipment', 'Vận đơn'),
            $l('multi_vendor.shipment.carrier', 'Carrier', 'Hãng vận chuyển'),
            $l('multi_vendor.shipment.tracking_code', 'Tracking code', 'Mã vận đơn'),
            $l('multi_vendor.shipment.note', 'Note', 'Ghi chú'),
            $l('multi_vendor.shipment.saved', 'Shipment saved.', 'Đã lưu vận đơn.')
        ));
    }

    public function uninstall()
    {
        try {
            //Please delete all values inserted in the installation step
            AdminConfig::where('key', $this->configKey)->delete();
            AdminConfig::where('code', $this->configKey.'_config')->delete();

            //Delete menu
            self::removeMenuBlock();

            //Language
            Languages::where('position', 'multi_vendor')->delete();

            //Default
            (new ExtensionModel)->uninstallExtension();
            $return = ['error' => 0, 'msg' => gp247_language_render('admin.extension.uninstall_success')];
        } catch (\Throwable $th) {
            $return = ['error' => 1, 'msg' => $th->getMessage()];
        }
        return $return;
    }

    public function enable(): array
    {
        $process = (new AdminConfig)
            ->where('group', $this->configGroup)
            ->where('key', $this->configKey)
            ->update(['value' => self::ON]);

        //Admin config home
        AdminHome::where('extension', $this->appPath)->update(['status' => 1]);

        if (!$process) {
            $return = ['error' => 1, 'msg' => gp247_language_render('admin.extension.action_error', ['action' => 'Enable'])];
        }
        $return = ['error' => 0, 'msg' => gp247_language_render('admin.extension.enable_success')];
        return $return;
    }

    public function disable(): array
    {
        $return = ['error' => 0, 'msg' => ''];
        $process = (new AdminConfig)
            ->where('key', $this->configKey)
            ->update(['value' => self::OFF]);
        if (!$process) {
            $return = ['error' => 1, 'msg' => gp247_language_render('admin.extension.action_error', ['action' => 'Disable'])];
        }
        //Admin config home
        AdminHome::where('extension', $this->appPath)->update(['status' => 0]);
        $return = ['error' => 0, 'msg' => gp247_language_render('admin.extension.disable_success')];
        return $return;
    }


    // Remove setup for store

    public function removeStore($storeId = null)
    {
        // code here
    }

    // Setup for store

    public function setupStore($storeId = null)
    {
       // code here
    }


    // Process when click button plugin in admin    
    
    public function clickApp()
    {
        return redirect(gp247_route_admin('admin_MultiVendor.index'));
    }

    /**
     * Get info plugin
     *
     * @return  [type]  [return description]
     */
    public function getInfo()
    {
        $arrData = [
            'title' => $this->title,
            'key' => $this->configKey,
            'code' => $this->configCode,
            'image' => $this->image,
            'permission' => self::ALLOW,
            'version' => $this->version,
            'auth' => $this->auth,
            'link' => $this->link,
            'value' => 0, // this return need for plugin shipping
            'appPath' => $this->appPath
        ];

        return $arrData;
    }

    /**
     * i18n rows of the shop-page trust signals (S5-3).
     */
    public static function seedTrustSignalLanguage(): void
    {
        $l = function (string $code, string $en, string $vi) {
            return [
                ['code' => $code, 'text' => $en, 'position' => 'multi_vendor', 'location' => 'en'],
                ['code' => $code, 'text' => $vi, 'position' => 'multi_vendor', 'location' => 'vi'],
            ];
        };
        Languages::insertOrIgnore(array_merge(
            $l('multi_vendor.shop.trust.dispute_rate', 'complaint rate', 'tỷ lệ khiếu nại'),
            $l('multi_vendor.shop.trust.handling_time', 'typical handling time', 'thời gian chuẩn bị hàng'),
            $l('multi_vendor.shop.trust.response_rate', 'complaints answered by the shop', 'khiếu nại được gian hàng trả lời'),
            $l('multi_vendor.shop.trust.review_reply_rate', 'reviews answered', 'đánh giá được trả lời'),
            $l('multi_vendor.shop.trust.window', 'based on :orders orders in the last :days days', 'dựa trên :orders đơn trong :days ngày gần nhất')
        ));
    }

    /**
     * i18n rows of vendor plan self-service (S5-5).
     */
    public static function seedPlanSelfServiceLanguage(): void
    {
        $l = function (string $code, string $en, string $vi) {
            return [
                ['code' => $code, 'text' => $en, 'position' => 'multi_vendor', 'location' => 'en'],
                ['code' => $code, 'text' => $vi, 'position' => 'multi_vendor', 'location' => 'vi'],
            ];
        };
        Languages::insertOrIgnore(array_merge(
            $l('multi_vendor.plan_self.title', 'My plan', 'Gói của tôi'),
            $l('multi_vendor.plan_self.help', 'Pick the plan that suits your shop. The fee is deducted from your next payout — there is nothing to pay separately.', 'Chọn gói phù hợp với gian hàng của bạn. Phí được trừ vào kỳ thanh toán kế tiếp, bạn không phải trả riêng.'),
            $l('multi_vendor.plan_self.current', 'Your current plan', 'Gói hiện tại'),
            $l('multi_vendor.plan_self.choose', 'Choose this plan', 'Chọn gói này'),
            $l('multi_vendor.plan_self.changed', 'Your plan has been changed.', 'Đã đổi gói cho gian hàng của bạn.'),
            $l('multi_vendor.plan_self.not_available', 'This plan is not available for you to choose.', 'Gói này không mở để tự chọn.'),
            $l('multi_vendor.plan_self.period_running', 'You have already paid for the current period. You can change plan when it ends, or ask the marketplace.', 'Bạn đã trả phí cho kỳ hiện tại. Hết kỳ bạn đổi gói được, hoặc liên hệ sàn.'),
            $l('multi_vendor.plan_self.too_many_products', 'This plan allows :cap products and your shop already lists :count.', 'Gói này cho tối đa :cap sản phẩm, gian hàng của bạn đang có :count.'),
            $l('multi_vendor.plan_self.unlimited', 'Unlimited', 'Không giới hạn'),
            $l('multi_vendor.plan_self.free', 'Free', 'Miễn phí'),
            $l('multi_vendor.plan_self.until', 'Paid until', 'Đã trả tới'),
            $l('multi_vendor.plan_self.empty', 'The marketplace has not published any plan yet.', 'Sàn chưa mở gói nào để tự chọn.'),
            $l('multi_vendor.plan_self.shelf', 'Open for vendors to choose', 'Mở cho vendor tự chọn')
        ));
    }
}
