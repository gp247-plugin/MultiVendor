<?php
namespace App\GP247\Plugins\MultiVendor\Admin\Controllers\Vendor;

use GP247\Shop\Models\ShopAttributeGroup;
use GP247\Core\Models\AdminCountry;
use GP247\Shop\Models\ShopCurrency;
use GP247\Shop\Models\ShopOrderStatus;
use GP247\Shop\Models\ShopPaymentStatus;
use GP247\Shop\Models\ShopShippingStatus;
use App\GP247\Plugins\MultiVendor\Admin\Models\AdminVendorOrder;
use Validator;
use App\GP247\Plugins\MultiVendor\Admin\Controllers\RootVendorController;

class VendorOrderController extends RootVendorController
{
    public $statusPayment, 
    $statusOrder, 
    $statusShipping, 
    $statusOrderMap, 
    $statusShippingMap, 
    $statusPaymentMap, 
    $currency, 
    $country, 
    $countryMap;

    public function __construct()
    {
        parent::__construct();
        $this->statusOrder    = ShopOrderStatus::getIdAll();
        $this->currency       = ShopCurrency::getListActive();
        $this->country        = AdminCountry::getCodeAll();
        $this->statusPayment  = ShopPaymentStatus::getIdAll();
        $this->statusShipping = ShopShippingStatus::getIdAll();

    }

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {

        $data = [
            'title'         => gp247_language_render('admin.order.list'),
            'subTitle'      => '',
            'icon'          => 'fa fa-indent',
            'removeList'    => 0, // 1 - Enable function delete list item
            'buttonRefresh' => 1, // 1 - Enable button refresh
            'css'           => '', 
            'js'            => '',
        ];
        //Process add content
        $data['menuRight']    = gp247_config_group('menuRight', \Request::route()->getName());
        $data['menuLeft']     = gp247_config_group('menuLeft', \Request::route()->getName());
        $data['topMenuRight'] = gp247_config_group('topMenuRight', \Request::route()->getName());
        $data['topMenuLeft']  = gp247_config_group('topMenuLeft', \Request::route()->getName());
        $data['blockBottom']  = gp247_config_group('blockBottom', \Request::route()->getName());

        $listTh = [
            'email'          => gp247_language_render('order.email'),
            'subtotal'       => '<i class="fa fa-shopping-cart" aria-hidden="true" title="'.gp247_language_render('order.subtotal').'"></i>',
            'shipping'       => '<i class="fa fa-truck" aria-hidden="true" title="'.gp247_language_render('order.shipping').'"></i>',
            'discount'       => '<i class="fa fa-tags" aria-hidden="true" title="'.gp247_language_render('order.discount').'"></i>',
            'tax'            => gp247_language_render('order.tax'),
            'total'          => gp247_language_render('order.total'),
            'payment_method' => '<i class="fa fa-credit-card" aria-hidden="true" title="'.gp247_language_render('admin.order.payment_method_short').'"></i>',
            'status'         => gp247_language_render('order.status'),
            'created_at'     => gp247_language_render('admin.created_at'),
            'action'         => gp247_language_render('action.title'),
        ];
        $sort_order   = gp247_clean(request('sort_order') ?? 'id_desc');
        $keyword      = gp247_clean(request('keyword') ?? '');
        $email        = gp247_clean(request('email') ?? '');
        $from_to      = gp247_clean(request('from_to') ?? '');
        $end_to       = gp247_clean(request('end_to') ?? '');
        $order_status = gp247_clean(request('order_status') ?? '');
        $arrSort = [
            'id__desc'         => gp247_language_render('filter_sort.id_desc'),
            'id__asc'          => gp247_language_render('filter_sort.id_asc'),
            'email__desc'      => gp247_language_render('filter_sort.alpha_desc', ['alpha' => 'Email']),
            'email__asc'       => gp247_language_render('filter_sort.alpha_asc', ['alpha' => 'Email']),
            'created_at__desc' => gp247_language_render('filter_sort.value_desc', ['value' => 'Date']),
            'created_at__asc'  => gp247_language_render('filter_sort.value_asc', ['value' => 'Date']),
        ];
        $dataSearch = [
            'keyword'      => $keyword,
            'email'        => $email,
            'from_to'      => $from_to,
            'end_to'       => $end_to,
            'sort_order'   => $sort_order,
            'arrSort'      => $arrSort,
            'order_status' => $order_status,
        ];

        $dataSearch['storeId'] = session('adminStoreId');
        $dataTmp = (new AdminVendorOrder)->getOrderListAdmin($dataSearch);

        $styleStatus = $this->statusOrder;
        array_walk($styleStatus, function (&$v, $k) {
            // Tailwind tint per status; the style name comes from the model's
            // map, so these classes are on the plugin's Tailwind safelist.
            $tint = [
                'success' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200',
                'primary' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200',
                'info' => 'bg-sky-100 text-sky-700 dark:bg-sky-900 dark:text-sky-200',
                'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-200',
                'danger' => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200',
                'secondary' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200',
            ][AdminVendorOrder::$mapStyleStatus[$k] ?? 'light'] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200';
            $v = '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ' . $tint . '">' . $v . '</span>';
        });
        $dataTr = [];
        foreach ($dataTmp as $key => $row) {
            $dataMap = [
                'email'          => $row['email'] ?? 'N/A',
                'subtotal'       => gp247_currency_render_symbol($row['subtotal'] ?? 0, $row['currency']),
                'shipping'       => gp247_currency_render_symbol($row['shipping'] ?? 0, $row['currency']),
                'discount'       => gp247_currency_render_symbol($row['discount'] ?? 0, $row['currency']),
                'tax'            => gp247_currency_render_symbol($row['tax'] ?? 0, $row['currency']),
                'total'          => gp247_currency_render_symbol($row['total'] ?? 0, $row['currency']),
                'payment_method' => ($row['payment_method'] ?? 'N/A').'('.$row['currency'] . '/' . $row['exchange_rate'].')',
                'status'         => $styleStatus[$row['status']],
                'created_at'     => $row['created_at'],
                'action'         => '
                                <a href="' . gp247_route_admin('vendor_admin_order.detail', ['id' => $row['id'] ? $row['id'] : 'not-found-id']) . '">
                                 <span title="' . gp247_language_render('admin.order.edit') . '" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-sm transition text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/40"><i class="fa fa-edit"></i></span>
                                </a>
                                '
                ,
            ];
            $arrAction = [
                '<a href="' . gp247_route_admin('vendor_admin_order.detail', ['id' => $row['id'] ? $row['id'] : 'not-found-id']) . '"  class="' . $this->actionItemClass() . '"><i class="fa fa-edit"></i> '.gp247_language_render('action.edit').'</a>',
                ];
            $arrAction[] = '<a href="#" onclick="deleteItem(\'' . $row['id'] . '\'); return false;"  title="' . gp247_language_render('action.delete') . '" class="' . $this->actionItemClass() . '"><i class="fas fa-trash-alt"></i> '.gp247_language_render('action.remove').'</a>';
            $action = $this->procesListAction($arrAction);
            $dataMap['action'] = $action;
            $dataTr[$row['id']] = $dataMap;
        }

        $data['listTh'] = $listTh;
        $data['dataTr'] = $dataTr;
        $data['pagination'] = $dataTmp->appends(request()->except(['_token', '_pjax']))->links($this->templatePathAdmin.'component.pagination');
        $data['resultItems'] = gp247_language_render('admin.result_item', ['item_from' => $dataTmp->firstItem(), 'item_to' => $dataTmp->lastItem(), 'total' =>  $dataTmp->total()]);

        //menuSort        
        $optionSort = '';
        foreach ($arrSort as $key => $sort) {
            $optionSort .= '<option  ' . (($sort_order == $key) ? "selected" : "") . ' value="' . $key . '">' . $sort . '</option>';
        }
        //=menuSort

        //menuSearch        
        $optionStatus = '';
        foreach ($this->statusOrder as $key => $status) {
            $optionStatus .= '<option  ' . (($order_status == $key) ? "selected" : "") . ' value="' . $key . '">' . $status . '</option>';
        }
        //menuSearch        
        $optionStatus = '';
        foreach ($this->statusOrder as $key => $status) {
            $optionStatus .= '<option  ' . (($order_status == $key) ? "selected" : "") . ' value="' . $key . '">' . $status . '</option>';
        }
        $data['topMenuRight'][] = '
                <form action="' . gp247_route_admin('vendor_admin_order.index') . '" id="button_search">
                    <div class="flex flex-wrap items-end gap-3">

                    <div style="width:130px">
                        <div class="form-group">
                            <label>'.gp247_language_render('action.sort').':</label>
                            <div class="input-group">
                                <select class="form-control rounded-0 select2" name="sort_order" id="sort_order">
                                '.$optionSort.'
                                </select>
                            </div>
                        </div>
                    </div> &nbsp;


                    <div style="width:130px">
                        <div class="form-group">
                            <label>'.gp247_language_render('action.from').':</label>
                            <div class="input-group">
                            <input type="text" name="from_to" id="from_to" class="block w-full rounded-s-lg border border-e-0 border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd" /> 
                            </div>
                        </div>
                    </div> &nbsp;
                    <div style="width:130px">
                        <div class="form-group">
                            <label>'.gp247_language_render('action.to').':</label>
                            <div class="input-group">
                            <input type="text" name="end_to" id="end_to" class="block w-full rounded-s-lg border border-e-0 border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd" /> 
                            </div>
                        </div>
                    </div> &nbsp;
                    <div style="width:150px">
                        <div class="form-group">
                            <label>'.gp247_language_render('admin.order.status').':</label>
                            <div class="input-group">
                            <select class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" name="order_status">
                            <option value="">'.gp247_language_render('admin.order.search_order_status').'</option>
                            ' . $optionStatus . '
                            </select>
                            </div>
                        </div>
                    </div> &nbsp;
                    <div style="width:150px">
                        <div class="form-group">
                            <label>'.gp247_language_render('admin.order.search_email').':</label>
                            <div class="input-group">
                                <input type="text" name="email" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" placeholder="' . gp247_language_render('admin.order.search_email') . '" value="' . $email . '">
                                <div>
                                    <button type="submit" class="inline-flex items-center justify-center rounded-e-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </form>';
    //=menuSearch

        return view($this->plugin->appPath.'::Admin.screen.vendor.list')
            ->with($data);
    }

    /**
     * Order detail
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function edit($id)
    {
        $checkOrder = $this->checkPermisisonItem($id);
        if (!$checkOrder) {
            return redirect()->route('vendor_admin.data_not_found')->with(['url' => url()->full()]);
        }
        $order = AdminVendorOrder::getOrderAdmin($id, session('adminStoreId'));
        $paymentMethod = [];
        $shippingMethod = [];
        $paymentMethodTmp = gp247_extension_get_via_code(code: 'payment', active: false);
        foreach ($paymentMethodTmp as $key => $value) {
            $paymentMethod[$key] = gp247_language_render($value->detail);
        }
        $shippingMethodTmp = gp247_extension_get_via_code(code: 'shipping', active: false);
        foreach ($shippingMethodTmp as $key => $value) {
            $shippingMethod[$key] = gp247_language_render($value->detail);
        }
        return view($this->plugin->appPath.'::Admin.screen.vendor.order_vendor_edit')->with(
            [
                "title"           => gp247_language_render('order.order_detail'),
                "subTitle"        => '',
                'icon'            => 'fa fa-file-text-o',
                "order"           => $order,
                "statusOrder"     => $this->statusOrder,
                "statusPayment"   => $this->statusPayment,
                "statusShipping"  => $this->statusShipping,
                'dataTotal'       => AdminVendorOrder::getOrderTotal($id),
                'attributesGroup' => ShopAttributeGroup::pluck('name', 'id')->all(),
                'paymentMethod'   => $paymentMethod,
                'shippingMethod'  => $shippingMethod,
                'country'         => $this->country,
            ]
        );
    }

    /**
     * process update order
     * @return [json]           [description]
     */
    /** Order columns a vendor may edit inline from the order detail screen. */
    private const UPDATABLE_ORDER_FIELDS = ['shipping_status'];

    public function postOrderUpdate()
    {
        $id = request('pk');
        $code = (string) request('name');
        $value = gp247_clean((string) request('value'));

        // WHY the allow-list: $code names the column to write and comes straight
        // from the browser. Without it a vendor could edit any column of their
        // own orders (status, totals, payment_status…) — only the shipping
        // status is editable on this screen (security.md).
        if (!in_array($code, self::UPDATABLE_ORDER_FIELDS, true)) {
            return response()->json(['error' => 1, 'msg' => 'Field not allowed: '.$code]);
        }

        $order = AdminVendorOrder::where('id', $id)->where('store_id', session('adminStoreId'))->first();
        if (!$order) {
            return response()->json(['error' => 1, 'msg' => gp247_language_render('vendor_admin.data_not_found_detail', ['msg' => 'order#'.$id]), 'detail' => '']);
        }
        $order->update([$code => $value]);
        return response()->json(
            ['error' => 0,'msg' => gp247_language_render('action.update_success')]
        );
    }

    /**
     * Printable packing slip for one of the vendor's own orders (S1-3). Reuses the
     * core invoice view (gp247-shop-admin::format.invoice) and builds the same
     * $data AdminOrderController::invoice() does; the only difference is the
     * store fence — the order must belong to session('adminStoreId').
     *
     * @param string $id Order id.
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     *
     * @aidlc-unit multi-vendor-pro
     * @aidlc-story US-multi-vendor-pro-vendor-order-fulfillment
     */
    public function print($id)
    {
        if (!(new AdminVendorOrder)->checkOrderAdmin($id)) {
            return redirect()->route('vendor_admin.data_not_found')->with(['url' => url()->full()]);
        }
        $order = AdminVendorOrder::getOrderAdmin($id, session('adminStoreId'));
        if (!$order) {
            return redirect()->route('vendor_admin.data_not_found')->with(['url' => url()->full()]);
        }

        $data = [
            'name' => $order['first_name'].' '.$order['last_name'],
            'address' => implode(', ', array_filter([
                $order['city'], $order['district'], $order['address1'],
                $order['address2'], $order['address3'], $order['country'],
            ])),
            'phone' => $order['phone'],
            'email' => $order['email'],
            'comment' => $order['comment'],
            'payment_method' => $order['payment_method'],
            'shipping_method' => $order['shipping_method'],
            'created_at' => $order['created_at'],
            'currency' => $order['currency'],
            'exchange_rate' => $order['exchange_rate'],
            'subtotal' => $order['subtotal'],
            'tax' => $order['tax'],
            'shipping' => $order['shipping'],
            'discount' => $order['discount'],
            'total' => $order['total'],
            'received' => $order['received'],
            'balance' => $order['balance'],
            'other_fee' => $order['other_fee'] ?? 0,
            'country' => $order['country'],
            'id' => $order->id,
            'details' => [],
        ];
        $attributesGroup = ShopAttributeGroup::pluck('name', 'id')->all();
        foreach ($order->details ?? [] as $key => $detail) {
            $arrAtt = json_decode((string) $detail->attribute, true);
            $name = $detail->name;
            if ($arrAtt) {
                $htmlAtt = '';
                foreach ($arrAtt as $groupAtt => $att) {
                    $htmlAtt .= ($attributesGroup[$groupAtt] ?? $groupAtt).':'.gp247_render_option_price($att, $order['currency'], $order['exchange_rate']);
                    $slug = explode('__', (string) $att)[2] ?? '';
                    if ($slug !== '') {
                        $htmlAtt .= ' ('.$slug.')';
                    }
                }
                $name = $detail->name.'('.strip_tags($htmlAtt).')';
            }
            $data['details'][] = [
                'no' => $key + 1,
                'sku' => $detail->sku,
                'name' => $name,
                'qty' => $detail->qty,
                'price' => $detail->price,
                'total_price' => $detail->total_price,
            ];
        }

        return view('gp247-shop-admin::format.invoice')->with($data);
    }

    /**
     * Check permisison item
     */
    public function checkPermisisonItem($id) {
        return (new AdminVendorOrder)->checkOrderAdmin($id);
    }
}
