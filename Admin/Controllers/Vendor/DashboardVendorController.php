<?php

namespace App\GP247\Plugins\MultiVendor\Admin\Controllers\Vendor;

use GP247\Shop\Models\ShopCustomer;
use GP247\Shop\Models\ShopProduct;
use Illuminate\Http\Request;
use App\GP247\Plugins\MultiVendor\Admin\Controllers\RootVendorController;

class DashboardVendorController extends RootVendorController
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function index(Request $request)
    {
        $data                   = [];
        $data['title']          = gp247_language_render('admin.dashboard');
        $data['totalOrder']     = gp247_vendor_count_order(session('adminStoreId'));
        // WHY ShopProduct (not the retired ShopProductStore): the 1-1 store
        // ownership refactor dropped the product-store pivot; a product now
        // carries store_id directly.
        $data['totalProduct']   = ShopProduct::where('store_id', session('adminStoreId'))->count();
        $data['topCustomer']    = ShopCustomer::where('store_id', session('adminStoreId'))
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();
        $data['totalCustomer']  = ShopCustomer::where('store_id', session('adminStoreId'))->count();


        //Order in 30 days
        $totalsInMonth = gp247_vendor_total_order_in_month(session('adminStoreId'))->keyBy('md')->toArray();
        $rangDays = new \DatePeriod(
            new \DateTime('-1 month'),
            new \DateInterval('P1D'),
            new \DateTime('+1 day')
        );
        $orderInMonth  = [];
        $amountInMonth  = [];
        foreach ($rangDays as $i => $day) {
            $date = $day->format('m-d');
            $orderInMonth[$date] = $totalsInMonth[$date]['total_order'] ?? '';
            $amountInMonth[$date] = ($totalsInMonth[$date]['total_amount'] ?? 0);
        }
        $data['orderInMonth'] = $orderInMonth;
        $data['amountInMonth'] = $amountInMonth;

        //End order in 30 days
        
        //Order in 12 months
        $totalsMonth = gp247_vendor_total_order_in_year(session('adminStoreId'))
            ->pluck('total_amount', 'ym')->toArray();
        $dataInYear = [];
        for ($i = 12; $i >= 0; $i--) {
            $date = date("Y-m", strtotime(date('Y-m-01') . " -$i months"));
            $dataInYear[$date] = $totalsMonth[$date] ?? 0;
        }
        $data['dataInYear'] = $dataInYear;
        //End order in 12 months

        return view($this->plugin->appPath.'::Admin.screen.vendor.dashboard', $data);
    }


    /**
     * Page not found
     *
     * @return  [type]  [return description]
     */
    public function dataNotFound()
    {
        $data = [
            'title' => gp247_language_render('vendor_admin.data_not_found'),
            'icon' => '',
            'url' => session('url'),
        ];
        return view($this->plugin->appPath.'::vendor_admin.data_not_found', $data);
    }


    /**
     * Page deny
     *
     * @return  [type]  [return description]
     */
    public function deny()
    {
        $data = [
            'title' => gp247_language_render('admin.deny'),
            'icon' => '',
            'method' => session('method'),
            'url' => session('url'),
        ];
        return view($this->plugin->appPath.'::Admin.deny', $data);
    }

    /**
     * Page deny
     *
     * @return  [type]  [return description]
     */
    public function accountInactive()
    {
        $data = [
            'title' => gp247_language_render('multi_vendor.account_inactive_title'),
            'icon' => '',
            'url' => session('url'),
        ];
        return view($this->plugin->appPath.'::Admin.account_inactive', $data);
    }

    /**
     * [denySingle description]
     *
     * @return  [type]  [return description]
     */
    public function denySingle()
    {
        $data = [
            'method' => session('method'),
            'url' => session('url'),
        ];
        return view($this->plugin->appPath.'::Admin.deny_single', $data);
    }


}
