<?php
use App\GP247\Plugins\MultiVendor\Admin\Models\AdminVendorCategory;
use App\GP247\Plugins\MultiVendor\Admin\Models\AdminVendorOrder;
use App\GP247\Plugins\MultiVendor\Admin\Models\AdminMoneyProcess;
use App\GP247\Plugins\MultiVendor\Models\PluginModel;

/**
 * Get id seller
 *
 * @return  [type]  [return description]
 */
if (!function_exists('vendor')) {
    function vendor() {
        return auth()->guard('vendor');
    }
}


/**
 * Get list category of vendor
 *
 * @return  [type]  [return description]
 */
if (!function_exists('gp247_vendor_get_categories_admin')) {
    function gp247_vendor_get_categories_admin() {
        return AdminVendorCategory::getCategoriesAdmin();
    }
}

/**
 * Get list category of vendor front
 *
 * @return  [type]  [return description]
 */
if (!function_exists('gp247_vendor_get_categories_front')) {
    function gp247_vendor_get_categories_front(string $storeId) {
        $storeId = $storeId ? $storeId: config('app.storeId');
        return (new AdminVendorCategory)->setStore($storeId)
            ->getData();
    }
}


/**
 * Count order of vendor
 */
if (!function_exists('gp247_vendor_count_order')) {
    function gp247_vendor_count_order(string $storeId) {
        return AdminVendorOrder::where('store_id', $storeId)->count();
    }
}


/**
 * Get total order store in month
 */
if (!function_exists('gp247_vendor_total_order_in_month')) {
    function gp247_vendor_total_order_in_month(string $storeId) {
        return (new AdminVendorOrder)->getSumOrderTotalStoreInMonth($storeId);
    }
}

/**
 * Get total order store in year
 */
if (!function_exists('gp247_vendor_total_order_in_year')) {
    function gp247_vendor_total_order_in_year(string $storeId) {
        return (new AdminVendorOrder)->getSumOrderTotalStoreInYear($storeId);
    }
}

/**
 * Get total order vendor country in year
 */
if (!function_exists('gp247_vendor_order_country_in_year')) {
    function gp247_vendor_order_country_in_year(string $storeId) {
        return (new AdminVendorOrder)->getSumVendorOrderCountryInYear($storeId);
    }
}

/**
 * Get total order vendor device in year
 */
if (!function_exists('gp247_vendor_order_device_in_year')) {
    function gp247_vendor_order_device_in_year(string $storeId) {
        return (new AdminVendorOrder)->getDeviceStoreInYear($storeId);
    }
}


/**
 * Get url store
 */
if (!function_exists('gp247_vendor_get_url')) {
    function gp247_vendor_get_url(string $storeId) {
        $store = \GP247\Core\Models\AdminStore::find($storeId);
        if (!$store) {
            return null;
        }
        // WHY: single-domain marketplace only — a vendor store is always reached
        // through the marketplace path /shop/{code}, never its own domain.
        return gp247_route_front('MultiVendor.detail', ['code' => $store->code]);
    }
}

/**
 * Get store by code
 */
if (!function_exists('gp247_vendor_get_store_by_code')) {
    function gp247_vendor_get_store_by_code(string $code) {
        return \GP247\Core\Models\AdminStore::with('descriptions')
        ->where('code', $code)
        ->where('status', 1) // open vendor
        ->first();
    }
}



/**
 * Get top new vendor
 */
if (!function_exists('gp247_vendor_top_new')) {
    function gp247_vendor_top_new() {
        return \GP247\Core\Models\AdminStore::where('status', 1)->where('id', '<>', GP247_STORE_ID_ROOT)->orderBy('created_at', 'desc')->limit(8)->get();
    }
}

//=============Pro=============

/**
 * Process order finish (status update = 5)
 */
if (!function_exists('gp247_order_success_finish')) {
    function gp247_order_success_finish($orderId) {
        return \GP247\Shop\Models\ShopOrder::where('id', $orderId)->update(['finish_date' => date('Y-m-d')]);
    }
}


/**
 * Process order unfinish (status update != 5)
 */
if (!function_exists('gp247_order_success_unfinish')) {
    function gp247_order_success_unfinish($orderId) {
        return \GP247\Shop\Models\ShopOrder::where('id', $orderId)->update(['finish_date' => null]);
    }
}

/**
 * Caculate amount payment done
 */
if (!function_exists('gp247_cal_amount_payment_done')) {
    function gp247_cal_amount_payment_done($storeId = null) {
        $data = (new AdminMoneyProcess)
        ->selectRaw('sum(amount) as amount, currency')
        ->where('status', 'done');
        if ($storeId) {
            $data = $data->where('store_id', $storeId);
        }
        $data = $data->groupBy('currency')
        ->get()
        ->toArray();
        return $data;
    }
}

/**
 * Caculate amount payment done
 */
if (!function_exists('gp247_cal_amount_payment_remaining')) {
    function gp247_cal_amount_payment_remaining($storeId = null) {
        $data = (new AdminMoneyProcess)
        ->selectRaw('sum(amount) as amount, currency')
        ->whereIn('status', ['processing','pending']);
        if ($storeId) {
            $data = $data->where('store_id', $storeId);
        }
        $data = $data->groupBy('currency')
        ->get()
        ->toArray();
        return $data;
    }
}

/**
 * Caculate amount order done
 */
if (!function_exists('gp247_cal_amount_order_done')) {
    function gp247_cal_amount_order_done($storeId = null) {
        $data = (new \GP247\Shop\Models\ShopOrder)
        ->selectRaw('sum(total) as total_sum, currency')
        ->where('status', 5);//Only process order completed
        if ($storeId) {
            $data = $data->where('store_id', $storeId);
        }
        $data = $data->groupBy('currency')
        ->get()
        ->toArray();
        return $data;
    }
}

