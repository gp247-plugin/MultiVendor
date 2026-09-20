<?php
#App\GP247\Plugins\MultiVendor\Admin\AdminController.php

namespace App\GP247\Plugins\MultiVendor\Controllers;

use GP247\Front\Controllers\RootFrontController;
use App\GP247\Plugins\MultiVendor\Models\VendorCategory;
use GP247\Shop\Models\ShopProduct;
use App\GP247\Plugins\MultiVendor\AppConfig;
use App\GP247\Plugins\MultiVendor\Dispute\Dispute;
use App\GP247\Plugins\MultiVendor\Kyc\Kyc;
use App\GP247\Plugins\MultiVendor\Storefront\ShopPageService;
use GP247\Shop\Models\ShopOrder;

class FrontController extends RootFrontController
{
    public $plugin;

    public function __construct()
    {
        parent::__construct();
        $this->plugin = new AppConfig;
    }

    /**
     * Marketplace directory: every open vendor store (S2-5).
     */
    public function vendorIndex(...$params)
    {
        if (GP247_SEO_LANG) {
            gp247_lang_switch($params[0] ?? '');
        }

        return $this->_vendorIndex();
    }

    public function vendorDetail(...$params)
    {
        if (GP247_SEO_LANG) {
            $lang = $params[0] ?? '';
            $code = $params[1] ?? '';
            gp247_lang_switch($lang);
        } else {
            $code = $params[0] ?? '';
        }
        return $this->_vendorDetail($code);
    }

    /**
     * /vendor — open stores as cards with a name search.
     *
     * @aidlc-unit multi-vendor-pro
     * @aidlc-story US-multi-vendor-pro-storefront-shop-page
     */
    protected function _vendorIndex()
    {
        $keyword = trim((string) gp247_clean(request('q', '')));
        $stores = ShopPageService::directory($keyword);
        $ids = collect($stores->items())->pluck('id')->map(fn ($id) => (string) $id)->all();

        $view = gp247_plugin_process_view($this->plugin->appPath, $this->GP247TemplatePath, 'vendor_index');
        gp247_check_view($view);

        return view($view, [
            'title' => gp247_language_render('multi_vendor.shop.directory_title'),
            'stores' => $stores,
            'counts' => ShopPageService::productCounts($ids),
            'ratings' => ShopPageService::ratingSummaries($ids),
            'verified' => Kyc::verifiedIds($ids), // S3-2 badge (Pro)
            'searchKeyword' => $keyword,
            'appPath' => $this->plugin->appPath,
            'layout_page' => 'vendor_index',
            'breadcrumbs' => [
                ['url' => '', 'title' => gp247_language_render('multi_vendor.shop.directory_title')],
            ],
        ]);
    }

    /**
     * Shop page of one store: header + tabs (products / reviews / info).
     *
     * @aidlc-unit multi-vendor-pro
     * @aidlc-story US-multi-vendor-pro-storefront-shop-page
     */
    protected function _vendorDetail($code = null)
    {
        $store = gp247_vendor_get_store_by_code((string) $code);
        if (!$store) {
            return abort(404);
        }

        $tab = (string) gp247_clean(request('tab', ShopPageService::TAB_PRODUCTS));
        if (!in_array($tab, ShopPageService::TABS, true)) {
            $tab = ShopPageService::TAB_PRODUCTS;
        }
        $stats = ShopPageService::stats($store);
        if ($tab === ShopPageService::TAB_REVIEWS && !$stats['ratingEnabled']) {
            $tab = ShopPageService::TAB_PRODUCTS;
        }
        $keyword = trim((string) gp247_clean(request('q', '')));
        $categoryId = trim((string) gp247_clean(request('cat', '')));
        $filterSort = (string) gp247_clean(request('filter_sort', ''));
        $filterSort = array_key_exists($filterSort, ShopPageService::SORTS) ? $filterSort : '';

        $products = $tab === ShopPageService::TAB_PRODUCTS
            ? ShopPageService::products($store->id, $keyword, $categoryId ?: null, $filterSort)
            : collect();
        if (method_exists($products, 'appends')) {
            $products->appends(array_filter(['q' => $keyword, 'cat' => $categoryId, 'filter_sort' => $filterSort]));
        }

        $view = gp247_plugin_process_view($this->plugin->appPath, $this->GP247TemplatePath, 'vendor_home');
        gp247_check_view($view);

        return view($view, [
            'title' => $store->getTitle(),
            'keyword' => $store->getKeyword(),
            'description' => $store->getDescription(),
            'og_image' => $store->logo ? gp247_file($store->logo) : null,
            'store' => $store,
            'stats' => $stats,
            'storeId' => $store->id,
            'storeCode' => $store->code,
            'tab' => $tab,
            'products' => $products,
            'banners' => ShopPageService::banners($store->id),
            'categories' => function_exists('gp247_vendor_get_categories_front') ? collect(gp247_vendor_get_categories_front($store->id)) : collect(),
            'searchKeyword' => $keyword,
            'categoryId' => $categoryId,
            'filter_sort' => $filterSort,
            'appPath' => $this->plugin->appPath,
            'layout_page' => 'vendor_home',
            'breadcrumbs' => [
                ['url' => gp247_route_front('MultiVendor.index'), 'title' => gp247_language_render('multi_vendor.shop.directory_title')],
                ['url' => '', 'title' => $store->getTitle()],
            ],
        ]);
    }

    public function categoryVendorDetail(...$params) 
    {
        if (GP247_SEO_LANG) {
            $lang = $params[0] ?? '';
            $alias = $params[1] ?? '';
            $storeId = $params[2] ?? '';
            gp247_lang_switch($lang);
        } else {
            $alias = $params[0] ?? '';
            $storeId = $params[1] ?? '';
        }
        return $this->_categoryVendorDetail($alias, $storeId);
    }

    /**
     * Category detail: list category child + product list
     * @param  [string] $alias
     * @return [view]
     */
    protected function _categoryVendorDetail($alias, $storeId)
    {
        $sortBy = 'sort';
        $sortOrder = 'asc';
        $filter_sort = request('filter_sort') ?? '';
        $filterArr = [
            'price_desc' => ['price', 'desc'],
            'price_asc' => ['price', 'asc'],
            'sort_desc' => ['sort', 'desc'],
            'sort_asc' => ['sort', 'asc'],
            'id_desc' => ['id', 'desc'],
            'id_asc' => ['id', 'asc'],
        ];
        if (array_key_exists($filter_sort, $filterArr)) {
            $sortBy    = $filterArr[$filter_sort][0];
            $sortOrder = $filterArr[$filter_sort][1];
        }

        $category = (new VendorCategory)->getDetail($alias, $type = 'alias', $storeId);
        if ($category) {
            $products = (new ShopProduct)
                ->getProductToCategoryStore($category->id)
                ->setLimit(gp247_config('product_list', $storeId))
                ->setStore($storeId)
                ->setSort([$sortBy, $sortOrder])
                ->setPaginate()
                ->getData();

            $subPath = 'vendor_product_list';
            $view = gp247_plugin_process_view($this->plugin->appPath, $this->GP247TemplatePath, $subPath);
            gp247_check_view($view);
    
            return view($view,
                array(
                    'title'             => $category->title,
                    'description'       => $category->description,
                    'keyword'           => $category->keyword,
                    'products'          => $products,
                    'storeId'           => $storeId,
                    'store'             => $category->store,
                    'stats'             => ShopPageService::stats($category->store),
                    'storeCode'         => $category->store->code,
                    'category'          => $category,
                    'layout_page'       => 'vendor_product_list',
                    'og_image'          => gp247_file($category->getImage()),
                    'filter_sort'       => $filter_sort,
                    'appPath'           => $this->plugin->appPath,
                    'breadcrumbs' => [
                        ['url'    => gp247_route_admin('MultiVendor.detail', ['code' => $category->store->code]), 'title' => $category->store->getTitle()],
                        ['url'    => '', 'title' => $category->title],
                    ],
                )
            );
        } else {
            return $this->itemNotFound();
        }

    }

    /**
     * S3-3: a signed-in customer opens a dispute on one of their own orders.
     * Redirects back to the order page with a notice (the dispute box shows it).
     */
    public function openDispute(\Illuminate\Http\Request $request, string $order)
    {
        $customer = customer()->user();
        $model = $customer ? ShopOrder::where('id', $order)->where('customer_id', $customer->id)->first() : null;
        if ($model === null) {
            return $this->disputeRedirect($order, gp247_language_render('multi_vendor.dispute.ineligible_not_owner'), 'error');
        }
        $data = $request->validate([
            'type' => 'required|in:'.implode(',', Dispute::TYPES),
            'reason' => 'required|string|min:'.Dispute::MIN_REASON.'|max:2000',
            'requested_amount' => 'nullable|numeric|min:0',
        ]);
        try {
            Dispute::open($model, (string) $customer->id, $data['type'], gp247_clean($data['reason']), isset($data['requested_amount']) ? (float) $data['requested_amount'] : null);
        } catch (\InvalidArgumentException $e) {
            return $this->disputeRedirect($order, gp247_language_render('multi_vendor.dispute.ineligible_'.$e->getMessage()), 'error');
        }

        return $this->disputeRedirect($order, gp247_language_render('multi_vendor.dispute.opened'));
    }

    /**
     * S3-3: the customer withdraws their still-open dispute.
     */
    public function withdrawDispute(string $order)
    {
        $customer = customer()->user();
        $dispute = $customer ? Dispute::forOrder($order) : null;
        $ok = $dispute !== null && Dispute::withdraw($dispute, (string) $customer->id);

        return $this->disputeRedirect($order, gp247_language_render($ok ? 'multi_vendor.dispute.withdrawn' : 'multi_vendor.dispute.not_live'), $ok ? 'success' : 'error');
    }

    private function disputeRedirect(string $orderId, string $notice, string $type = 'success')
    {
        $url = \Illuminate\Support\Facades\Route::has('customer.order_detail') ? gp247_route_front('customer.order_detail', ['id' => $orderId]) : url()->previous();

        return redirect($url)->with(['multi_vendor_dispute_notice' => $notice, 'multi_vendor_dispute_notice_type' => $type]);
    }
}
