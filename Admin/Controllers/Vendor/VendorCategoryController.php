<?php
namespace App\GP247\Plugins\MultiVendor\Admin\Controllers\Vendor;

use GP247\Core\Models\AdminLanguage;
use Validator;
use App\GP247\Plugins\MultiVendor\Admin\Models\AdminVendorCategory;
use App\GP247\Plugins\MultiVendor\Admin\Controllers\RootVendorController;

class VendorCategoryController extends RootVendorController
{
    public $languages;

    public function __construct()
    {
        parent::__construct();
        $this->languages = AdminLanguage::getListActive();

    }

    public function index()
    {
        $data = [
            'title'         => gp247_language_render($this->plugin->appPath.'::category_store.admin.list'),
            'subTitle'      => '',
            'icon'          => 'fa fa-indent',
            'urlDeleteItem' => gp247_route_admin('vendor_admin_category.delete'),
            'removeList'    => 1, // 1 - Enable function delete list item
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
            'image'  => gp247_language_render($this->plugin->appPath.'::category_store.image'),
            'title'  => gp247_language_render($this->plugin->appPath.'::category_store.title'),
            'status' => gp247_language_render($this->plugin->appPath.'::category_store.status'),
            'sort'   => gp247_language_render($this->plugin->appPath.'::category_store.sort'),
            'action' => gp247_language_render($this->plugin->appPath.'::category_store.admin.action'),
        ];
        $sort_order = gp247_clean(request('sort_order') ?? 'id_desc');
        $keyword    = gp247_clean(request('keyword') ?? '');
        $arrSort = [
            'id__desc' => gp247_language_render($this->plugin->appPath.'::category_store.admin.sort_order.id_desc'),
            'id__asc' => gp247_language_render($this->plugin->appPath.'::category_store.admin.sort_order.id_asc'),
            'title__desc' => gp247_language_render($this->plugin->appPath.'::category_store.admin.sort_order.title_desc'),
            'title__asc' => gp247_language_render($this->plugin->appPath.'::category_store.admin.sort_order.title_asc'),
        ];
        
        $dataSearch = [
            'keyword'    => $keyword,
            'sort_order' => $sort_order,
            'arrSort'    => $arrSort,
        ];
        $dataTmp = (new AdminVendorCategory)->getVendorCategoryListAdmin($dataSearch);

        $dataTr = [];
        foreach ($dataTmp as $key => $row) {
            $dataTr[$row['id']] = [
                'image' => gp247_image_render($row->getThumb(), '50px', '50px', $row['title']),
                'title' => $row['title'],
                'status' => $row['status'] ? '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200">ON</span>' : '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200">OFF</span>',
                'sort' => $row['sort'],
                'action' => '
                    <a href="' . gp247_route_admin('vendor_admin_category.edit', ['id' => $row['id'] ? $row['id'] : 'not-found-id']) . '"><span title="' . gp247_language_render($this->plugin->appPath.'::category_store.admin.edit') . '" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-sm transition text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/40"><i class="fa fa-edit"></i></span></a>&nbsp;

                    <span onclick="deleteItem(\'' . $row['id'] . '\'); return false;"  title="' . gp247_language_render('admin.delete') . '" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-sm transition text-red-600 hover:bg-red-50 dark:hover:bg-red-900/40"><i class="fas fa-trash-alt"></i></span>'
                ,
            ];
        }

        $data['listTh'] = $listTh;
        $data['dataTr'] = $dataTr;
        $data['pagination'] = $dataTmp->appends(request()->except(['_token', '_pjax']))->links($this->templatePathAdmin.'component.pagination');
        $data['resultItems'] = gp247_language_render('admin.result_item', ['item_from' => $dataTmp->firstItem(), 'item_to' => $dataTmp->lastItem(), 'total' =>  $dataTmp->total()]);


        //menuRight
        $data['menuRight'][] = '<a href="' . gp247_route_admin('vendor_admin_category.create') . '" class="inline-flex items-center justify-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium transition bg-green-600 text-white hover:bg-green-700" title="New" id="button_create_new">
        <i class="fa fa-plus" title="'.gp247_language_render('admin.add_new').'"></i>
        </a>';
        //=menuRight

        //menuSort        
        $optionSort = '';
        foreach ($arrSort as $key => $sort) {
            $optionSort .= '<option  ' . (($sort_order == $key) ? "selected" : "") . ' value="' . $key . '">' . $sort . '</option>';
        }
        //=menuSort

        //menuSearch        
        $data['topMenuRight'][] = '
                <form action="' . gp247_route_admin('vendor_admin_category.index') . '" id="button_search">
                <div class="flex w-full max-w-sm items-center">
                <select class="form-control rounded-0 select2" name="sort_order" id="sort_order">
                '.$optionSort.'
                </select> &nbsp;
                    <input type="text" name="keyword" class="block w-full rounded-s-lg border border-e-0 border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" placeholder="' . gp247_language_render($this->plugin->appPath.'::category_store.admin.search_place') . '" value="' . $keyword . '">
                    <div>
                        <button type="submit" class="inline-flex items-center justify-center rounded-e-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"><i class="fas fa-search"></i></button>
                    </div>
                </div>
                </form>';
        //=menuSearch

        return view($this->plugin->appPath.'::Admin.screen.vendor.list')
            ->with($data);
    }

    /*
     * Form create new order in admin
     * @return [type] [description]
     */
    public function create()
    {
        $data = [
            'title'             => gp247_language_render($this->plugin->appPath.'::category_store.admin.add_new_title'),
            'subTitle'          => '',
            'title_description' => gp247_language_render($this->plugin->appPath.'::category_store.admin.add_new_des'),
            'icon'              => 'fa fa-plus',
            'languages'         => $this->languages,
            'category_store'    => [],
            'appPath'        => $this->plugin->appPath,
            'url_action'        => gp247_route_admin('vendor_admin_category.create'),
        ];

        return view($this->plugin->appPath.'::Admin.screen.vendor.category_store')
            ->with($data);
    }

    /*
     * Post create new order in admin
     * @return [type] [description]
     */
    public function postCreate()
    {
        $data = request()->all();

        $langFirst = array_key_first(gp247_language_all()->toArray()); //get first code language active
        $data['alias'] = !empty($data['alias'])?$data['alias']:$data['descriptions'][$langFirst]['title'];
        $data['alias'] = gp247_word_format_url($data['alias']);
        $data['alias'] = gp247_word_limit($data['alias'], 100);

        $validator = Validator::make($data, [
                'sort'                   => 'numeric|min:0',
                'alias'                  => 'required|regex:/(^([0-9A-Za-z\-_]+)$)/|string|max:100',
                'descriptions.*.title'   => 'required|string|max:200',
                'descriptions.*.keyword' => 'nullable|string|max:200',
                'descriptions.*.description' => 'nullable|string|max:300',
            ], [
                'descriptions.*.title.required' => gp247_language_render('validation.required', ['attribute' => gp247_language_render($this->plugin->appPath.'::category_store.title')]),
                'alias.regex' => gp247_language_render($this->plugin->appPath.'::category_store.alias_validate'),
            ]
        );

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($data);
        }
        $dataInsert = [
            'image'    => $data['image'],
            'alias'    => $data['alias'],
            'status'   => !empty($data['status']) ? 1 : 0,
            'sort'     => (int) $data['sort'],
            'store_id' => session('adminStoreId'),
        ];
        $category_store = AdminVendorCategory::createVendorCategoryAdmin($dataInsert);
        $dataDes = [];
        $languages = $this->languages;
        foreach ($languages as $code => $value) {
            $dataDes[] = [
                'vendor_category_id' => $category_store->id,
                'lang'        => $code,
                'title'       => $data['descriptions'][$code]['title'],
                'keyword'     => $data['descriptions'][$code]['keyword'],
                'description' => $data['descriptions'][$code]['description'],
            ];
        }
        AdminVendorCategory::insertDescriptionAdmin($dataDes);

        gp247_cache_clear('cache_category_store');

        return redirect()->route('vendor_admin_category.index')->with('success', gp247_language_render($this->plugin->appPath.'::category_store.admin.create_success'));

    }

    /*
     * Form edit
     */
    public function edit($id)
    {
        $category_store = AdminVendorCategory::getVendorCategoryAdmin($id);

        if (!$category_store) {
            return redirect()->route('vendor_admin.data_not_found')->with(['url' => url()->full()]);
        }

        $data                   =  [
            'title'             => gp247_language_render($this->plugin->appPath.'::category_store.admin.edit'),
            'subTitle'          => '',
            'title_description' => '',
            'icon'              => 'fa fa-edit',
            'languages'         => $this->languages,
            'category_store'    => $category_store,
            'appPath'        => $this->plugin->appPath,
            'url_action'        => gp247_route_admin('vendor_admin_category.edit', ['id' => $category_store['id']]),
        ];
        return view($this->plugin->appPath.'::Admin.screen.vendor.category_store')
            ->with($data);
    }

    /*
     * update status
     */
    public function postEdit($id)
    {
        $category_store = AdminVendorCategory::getVendorCategoryAdmin($id);
        if (!$category_store) {
            return redirect()->route('vendor_admin.data_not_found')->with(['url' => url()->full()]);
        }

        $data = request()->all();

        $langFirst = array_key_first(gp247_language_all()->toArray()); //get first code language active
        $data['alias'] = !empty($data['alias'])?$data['alias']:$data['descriptions'][$langFirst]['title'];
        $data['alias'] = gp247_word_format_url($data['alias']);
        $data['alias'] = gp247_word_limit($data['alias'], 100);

        $validator = Validator::make($data, [
            'sort'                   => 'numeric|min:0',
            'alias'                  => 'required|regex:/(^([0-9A-Za-z\-_]+)$)/|string|max:100',
            'descriptions.*.title'   => 'required|string|max:200',
            'descriptions.*.keyword' => 'nullable|string|max:200',
            'descriptions.*.description' => 'nullable|string|max:300',
            ], [
                'descriptions.*.title.required' => gp247_language_render('validation.required', ['attribute' => gp247_language_render($this->plugin->appPath.'::category_store.title')]),
                'alias.regex'                   => gp247_language_render($this->plugin->appPath.'::category_store.alias_validate'),
            ]
        );

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($data);
        }
        //Edit
        $dataUpdate = [
            'image'    => $data['image'],
            'alias'    => $data['alias'],
            'sort'     => $data['sort'],
            'status'   => empty($data['status']) ? 0 : 1,
            'store_id' => session('adminStoreId'),
        ];

        $category_store->update($dataUpdate);
        $category_store->descriptions()->delete();
        $dataDes = [];
        foreach ($data['descriptions'] as $code => $row) {
            $dataDes[] = [
                'vendor_category_id' => $id,
                'lang'        => $code,
                'title'       => $row['title'],
                'keyword'     => $row['keyword'],
                'description' => $row['description'],
            ];
        }
        AdminVendorCategory::insertDescriptionAdmin($dataDes);

        gp247_cache_clear('cache_category_store');

    //
        return redirect()->route('vendor_admin_category.index')->with('success', gp247_language_render($this->plugin->appPath.'::category_store.admin.edit_success'));

    }

    /*
    Delete list Item
    Need mothod destroy to boot deleting in model
    */
    public function deleteList()
    {
        if (!request()->ajax()) {
            return response()->json(['error' => 1, 'msg' => gp247_language_render('admin.method_not_allow')]);
        } else {
            $ids = request('ids');
            $arrID = explode(',', $ids);
            $arrDontPermission = [];
            foreach ($arrID as $key => $id) {
                if(!$this->checkPermisisonItem($id)) {
                    $arrDontPermission[] = $id;
                }
            }
            if (count($arrDontPermission)) {
                return response()->json(['error' => 1, 'msg' => gp247_language_render('admin.remove_dont_permisison') . ': ' . json_encode($arrDontPermission)]);
            }
            AdminVendorCategory::destroy($arrID);
            gp247_cache_clear('cache_category_store');
            return response()->json(['error' => 0, 'msg' => '']);
        }
    }

    /**
     * Check permisison item
     */
    public function checkPermisisonItem($id) {
        return AdminVendorCategory::getVendorCategoryAdmin($id);
    }

}
