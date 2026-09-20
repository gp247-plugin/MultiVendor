<?php
namespace App\GP247\Plugins\MultiVendor\Admin\Controllers\Vendor;

use GP247\Shop\Models\ShopSupplier;
use GP247\Core\Models\AdminCustomField;
use Validator;
use App\GP247\Plugins\MultiVendor\Admin\Controllers\RootVendorController;


class VendorSupplierController extends RootVendorController
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function index()
    {
        $supplier = new ShopSupplier;
        $data = [
            'title' => gp247_language_render('admin.supplier.list'),
            'title_action' => '<i class="fa fa-plus" aria-hidden="true"></i> ' . gp247_language_render('admin.supplier.add_new_title'),
            'subTitle' => '',
            'icon' => 'fa fa-indent',
            'urlDeleteItem' => gp247_route_admin('vendor_admin_supplier.delete'),
            'removeList' => 0, // 1 - Enable function delete list item
            'buttonRefresh' => 0, // 1 - Enable button refresh
            'css' => '',
            'js' => '',
            'url_action' => gp247_route_admin('vendor_admin_supplier.create'),
            'customFields'      => (new AdminCustomField)->getCustomField($type = 'shop_supplier'),
        ];

        $listTh = [
            'name' => gp247_language_render('admin.supplier.name'),
            'image' => gp247_language_render('admin.supplier.image'),
            'email' => gp247_language_render('admin.supplier.email'),
            'sort' => gp247_language_render('admin.supplier.sort'),
            'action' => gp247_language_render('action.title'),
        ];
        $obj = new ShopSupplier;
        $obj = $obj->where('store_id', session('adminStoreId'))->orderBy('id', 'desc');
        $dataTmp = $obj->paginate(20);

        $dataTr = [];
        foreach ($dataTmp as $key => $row) {
            $dataTr[$row['id']] = [
                'name' => $row['name'],
                'image' => gp247_image_render($row->getThumb(), '50px', '50px', $row['name']),
                'email' => $row['email'],
                'sort' => $row['sort'],
                'action' => '
                    <a href="' . gp247_route_admin('vendor_admin_supplier.edit', ['id' => $row['id'] ? $row['id'] : 'not-found-id']) . '"><span title="' . gp247_language_render('action.edit') . '" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-sm transition text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/40"><i class="fa fa-edit"></i></span></a>&nbsp;

                  <span onclick="deleteItem(\'' . $row['id'] . '\'); return false;"  title="' . gp247_language_render('action.delete') . '" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-sm transition text-red-600 hover:bg-red-50 dark:hover:bg-red-900/40"><i class="fas fa-trash-alt"></i></span>
                  ',
            ];
        }
        $data['supplier'] = $supplier;
        $data['listTh'] = $listTh;
        $data['dataTr'] = $dataTr;
        $data['pagination'] = $dataTmp->appends(request()->except(['_token', '_pjax']))->links($this->templatePathAdmin.'component.pagination');
        $data['resultItems'] = gp247_language_render('admin.result_item', ['item_from' => $dataTmp->firstItem(), 'item_to' => $dataTmp->lastItem(), 'total' =>  $dataTmp->total()]);

        $data['layout'] = 'index';
        return view($this->plugin->appPath.'::Admin.screen.vendor.supplier')
            ->with($data);
    }
    /**
     * Post create new item in admin
     * @return [type] [description]
     */
    public function postCreate()
    {
        $data = request()->all();

        $data['alias'] = !empty($data['alias'])?$data['alias']:$data['name'];
        $data['alias'] = gp247_word_format_url($data['alias']);
        $data['alias'] = gp247_word_limit($data['alias'], 100);
        $arrValidation = [
            'image' => 'required',
            'sort' => 'numeric|min:0',
            'name' => 'required|string|max:100',
            'alias' => 'required|regex:/(^([0-9A-Za-z\-_]+)$)/|unique:"'.ShopSupplier::class.'",alias|string|max:100',
            'url' => 'url|nullable',
            'email' => 'email|nullable',
        ];
        //Custom fields
        $customFields = (new AdminCustomField)->getCustomField($type = 'shop_supplier');
        if ($customFields) {
            foreach ($customFields as $field) {
                if ($field->required) {
                    $arrValidation['fields.'.$field->code] = 'required';
                }
            }
        }
        $validator = Validator::make($data, $arrValidation, [
            'name.required' => gp247_language_render('validation.required', ['attribute' => gp247_language_render('admin.supplier.name')]),
            'alias.regex' => gp247_language_render('admin.supplier.alias_validate'),
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($data);
        }

        $dataCreate = [
            'image'    => $data['image'],
            'name'     => $data['name'],
            'alias'    => $data['alias'],
            'url'      => $data['url'],
            'email'    => $data['email'],
            'address'  => $data['address'],
            'phone'    => $data['phone'],
            'sort'     => (int) $data['sort'],
            'store_id' => session('adminStoreId')
        ];
        $dataCreate = gp247_clean($dataCreate, [], true);
        $supplier = ShopSupplier::create($dataCreate);

        //Insert custom fields
        $fields = $data['fields'] ?? [];
        gp247_custom_field_update($fields, $supplier->id, 'shop_supplier');

        return redirect()->route('vendor_admin_supplier.index')->with('success', gp247_language_render('action.create_success'));
    }

    /**
     * Form edit
     */
    public function edit($id)
    {
        $supplier = ShopSupplier::find($id);
        if (!$supplier) {
            return 'No data';
        }
        $data = [
        'title' => gp247_language_render('admin.supplier.list'),
        'title_action' => '<i class="fa fa-edit" aria-hidden="true"></i> ' . gp247_language_render('action.edit'),
        'subTitle' => '',
        'icon' => 'fa fa-indent',
        'urlDeleteItem' => gp247_route_admin('vendor_admin_supplier.delete'),
        'removeList' => 0, // 1 - Enable function delete list item
        'buttonRefresh' => 0, // 1 - Enable button refresh
        'css' => '',
        'js' => '',
        'url_action' => gp247_route_admin('vendor_admin_supplier.edit', ['id' => $supplier['id']]),
        'supplier' => $supplier,
        'id' => $id,
        'customFields'      => (new AdminCustomField)->getCustomField($type = 'shop_supplier'),
    ];

        $listTh = [
        'name' => gp247_language_render('admin.supplier.name'),
        'image' => gp247_language_render('admin.supplier.image'),
        'email' => gp247_language_render('admin.supplier.email'),
        'sort' => gp247_language_render('admin.supplier.sort'),
        'action' => gp247_language_render('action.title'),
    ];

        $obj = new ShopSupplier;
        $obj = $obj->orderBy('id', 'desc');
        $dataTmp = $obj->paginate(20);

        $dataTr = [];
        foreach ($dataTmp as $key => $row) {
            $dataTr[$row['id']] = [
            'name' => $row['name'],
            'image' => gp247_image_render($row->getThumb(), '50px', '50px', $row['name']),
            'email' => $row['email'],
            'sort' => $row['sort'],
            'action' => '
                <a href="' . gp247_route_admin('vendor_admin_supplier.edit', ['id' => $row['id'] ? $row['id'] : 'not-found-id']) . '"><span title="' . gp247_language_render('action.edit') . '" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-sm transition text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/40"><i class="fa fa-edit"></i></span></a>&nbsp;

                <span onclick="deleteItem(\'' . $row['id'] . '\'); return false;"  title="' . gp247_language_render('action.delete') . '" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-sm transition text-red-600 hover:bg-red-50 dark:hover:bg-red-900/40"><i class="fas fa-trash-alt"></i></span>
                ',
        ];
        }

        $data['listTh'] = $listTh;
        $data['dataTr'] = $dataTr;
        $data['pagination'] = $dataTmp->appends(request()->except(['_token', '_pjax']))->links($this->templatePathAdmin.'component.pagination');
        $data['resultItems'] = gp247_language_render('admin.result_item', ['item_from' => $dataTmp->firstItem(), 'item_to' => $dataTmp->lastItem(), 'total' =>  $dataTmp->total()]);

        $data['layout'] = 'edit';
        return view($this->plugin->appPath.'::Admin.screen.vendor.supplier')
        ->with($data);
    }

    /**
     * update supplier
     */
    public function postEdit($id)
    {
        $supplier = ShopSupplier::find($id);
        $data = request()->all();

        $data['alias'] = !empty($data['alias'])?$data['alias']:$data['name'];
        $data['alias'] = gp247_word_format_url($data['alias']);
        $data['alias'] = gp247_word_limit($data['alias'], 100);
        $arrValidation = [
            'image' => 'required',
            'sort' => 'numeric|min:0',
            'name' => 'required|string|max:100',
            'alias' => 'required|regex:/(^([0-9A-Za-z\-_]+)$)/|unique:"'.ShopSupplier::class.'",alias,' . $supplier->id . ',id|string|max:100',
            'url' => 'url|nullable',
            'email' => 'email|nullable',
        ];
        //Custom fields
        $customFields = (new AdminCustomField)->getCustomField($type = 'shop_supplier');
        if ($customFields) {
            foreach ($customFields as $field) {
                if ($field->required) {
                    $arrValidation['fields.'.$field->code] = 'required';
                }
            }
        }
        $validator = Validator::make($data, $arrValidation, [
            'name.required' => gp247_language_render('validation.required', ['attribute' => gp247_language_render('admin.supplier.name')]),
            'alias.regex' => gp247_language_render('admin.supplier.alias_validate'),
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($data);
        }
        //Edit

        $dataUpdate = [
            'image' => $data['image'],
            'name' => $data['name'],
            'alias' => $data['alias'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'url' => $data['url'],
            'address' => $data['address'],
            'sort' => (int) $data['sort'],

        ];
        $dataUpdate = gp247_clean($dataUpdate, [], true);
        $supplier->update($dataUpdate);

        //Insert custom fields
        $fields = $data['fields'] ?? [];
        gp247_custom_field_update($fields, $supplier->id, 'shop_supplier');

        return redirect()->back()->with('success', gp247_language_render('action.edit_success'));
    }

    /*
    Delete list item
    Need mothod destroy to boot deleting in model
     */
    public function deleteList()
    {
        if (!request()->ajax()) {
            return response()->json(['error' => 1, 'msg' => gp247_language_render('admin.method_not_allow')]);
        } else {
            $ids = request('ids');
            $arrID = explode(',', $ids);
            ShopSupplier::destroy($arrID);
            return response()->json(['error' => 0, 'msg' => '']);
        }
    }
}
