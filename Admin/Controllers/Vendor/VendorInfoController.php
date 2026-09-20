<?php
namespace App\GP247\Plugins\MultiVendor\Admin\Controllers\Vendor;

use GP247\Core\Models\AdminStore;
use GP247\Core\Models\AdminLanguage;
use GP247\Shop\Models\ShopCurrency;
use Illuminate\Support\Facades\Artisan;
use Validator;
use DB;
use App\GP247\Plugins\MultiVendor\Admin\Controllers\RootVendorController;

class VendorInfoController extends RootVendorController
{
    public $templates, $currencies, $languages;

    public function __construct()
    {
        parent::__construct();
        $this->templates = gp247_front_get_all_template_installed();
        $this->currencies = ShopCurrency::getCodeActive();
        $this->languages = AdminLanguage::getListActive();
    }
    
    /**
     * Form create new store in admin
     * @return [type] [description]
     */
    public function vendorUpdate()
    {
        $data = [
            'title' => gp247_language_render('multi_vendor.update_info_store_title'),
            'subTitle' => '',
        ];
        
        $store = AdminStore::find(session('adminStoreId'));
        if (!$store) {
            $data = [
                'title' => gp247_language_render('admin.store.title'),
                'subTitle' => '',
                'dataNotFound' => 1
            ];
            return view($this->plugin->appPath.'::Admin.screen.vendor.store_info')
            ->with($data);
        }
        $data['currencies'] =$this->currencies;
        $data['templates'] = $this->templates;
        $data['languages'] = $this->languages;
        $data['store'] = $store;
        $data['storeId'] = $store->id;

        return view($this->plugin->appPath.'::Admin.screen.vendor.store_info')
            ->with($data);
    }


    /*
    Update value config
    */
    /**
     * Store columns a vendor may edit from their own store-info screen.
     * currency/language/code/type/template are rejected by their own branches
     * above (single-domain marketplace: they follow the marketplace, not the
     * vendor).
     */
    private const UPDATABLE_STORE_FIELDS = [
        'logo', 'icon', 'og_image', 'phone', 'long_phone', 'time_active',
        'address', 'office', 'warehouse', 'email',
    ];

    public function updateInfo()
    {
        $storeId = session('adminStoreId');
        $data      = request()->all();
        $data = gp247_clean($data, [], true);
        $fieldName = $data['name'];
        $value     = $data['value'];
        $parseName = explode('__', $fieldName);
        $name      = $parseName[0];
        $lang      = $parseName[1] ?? '';
        $msg       = 'Update success';
        // Check store
        $store     = AdminStore::find($storeId);
        if (!$store) {
            return response()->json(['error' => 1, 'msg' => 'Store not found!']);
        }

        if (!$lang) {
            try {
                if ($name == 'type') {
                    // Can not change type in here
                    $error = 1;
                    $msg = gp247_language_render('admin.store.value_cannot_change');
                } elseif ($name == 'domain' || $name == 'code') {
                    $error = 1;
                    $msg = gp247_language_render('admin.store.value_cannot_change');
                } elseif (in_array($name, ['language', 'currency'], true)) {
                    // WHY: single-domain marketplace — currency/language follow the
                    // marketplace, a vendor may not change them for its own store.
                    $error = 1;
                    $msg = gp247_language_render('admin.store.value_cannot_change');
                } elseif ($name == 'template') {
                    
                    $store = AdminStore::where('id', $storeId)->first();
                    $templateKey = $value;
                    $oldTepmlateKey = $store->template;
                    
                    $classTemplate = gp247_extension_get_namespace(type:'Templates', key:$templateKey);
                    $classTemplate = $classTemplate . '\AppConfig';
                    $oldClassTemplate = gp247_extension_get_namespace(type:'Templates', key:$oldTepmlateKey);
                    $oldClassTemplate = $oldClassTemplate . '\AppConfig';
                    // Check class exist
                    if (class_exists($oldClassTemplate)) {
                        (new $oldClassTemplate)->removeStore($storeId);
                    }
                    if (class_exists($classTemplate)) {
                        (new $classTemplate)->setupStore($storeId);
                    }

                    AdminStore::where('id', $storeId)->update([$name => $templateKey]);

                    gp247_notice_add(type:'template', typeId: $templateKey, content:'admin_notice.gp247_template_change::old__'.$oldTepmlateKey.'::new__'.$templateKey);
                    gp247_extension_after_update();

                    $error = 0;
                } elseif (!in_array($name, self::UPDATABLE_STORE_FIELDS, true)) {
                    // WHY an allow-list on top of the checks above: $name is the
                    // column to write and comes from the browser. The deny-list
                    // alone left every other column of the vendor's own store
                    // writable — including `partner` and `status`, which decide
                    // what the vendor is allowed to do (security.md).
                    $error = 1;
                    $msg = gp247_language_render('admin.store.value_cannot_change');
                } else {
                    AdminStore::where('id', $storeId)->update([$name => $value]);
                    $error = 0;
                }
            } catch (\Throwable $e) {
                $error = 1;
                $msg = $e->getMessage();
            }
        } else {
            // Process description
            $dataUpdate = [
                'storeId' => $storeId,
                'lang' => $lang,
                'name' => $name,
                'value' => $value,
            ];
            $dataUpdate = gp247_clean($dataUpdate, [], true);
            try {
                AdminStore::updateDescription($dataUpdate);
                $error = 0;
            } catch (\Throwable $e) {
                $error = 1;
                $msg = $e->getMessage();
            }
        }
        return response()->json(['error' => $error, 'msg' => $msg]);
    }


}
