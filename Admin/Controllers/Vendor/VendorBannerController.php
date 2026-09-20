<?php
namespace App\GP247\Plugins\MultiVendor\Admin\Controllers\Vendor;

use Validator;
use GP247\Front\Models\FrontBanner;
use GP247\Front\Models\FrontBannerType;
use App\GP247\Plugins\MultiVendor\Admin\Controllers\RootVendorController;

class VendorBannerController extends RootVendorController
{
    protected $arrTarget;
    protected $dataType;
    public function __construct()
    {
        parent::__construct();
        $this->arrTarget = ['_blank' => '_blank', '_self' => '_self'];
        $this->dataType  = (new FrontBannerType)->pluck('name', 'code')->all();
        if(gp247_config_global('MultiVendor')) {
            $this->dataType['background-store'] = 'Background store';
            $this->dataType['breadcrumb-store'] = 'Breadcrumb store';
        }
        ksort($this->dataType);
    }

    public function index()
    {
        $data = [
            'title'         => gp247_language_render('admin.banner.list'),
            'subTitle'      => '',
            'icon'          => 'fa fa-indent',
            'urlDeleteItem' => gp247_route_admin('vendor_admin_banner.delete'),
            'removeList'    => 0, // 1 - Enable function delete list item
            'buttonRefresh' => 0, // 1 - Enable button refresh
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
            'image'  => gp247_language_render('admin.banner.image'),
            'title'  => gp247_language_render('admin.banner.title'),
            'url'    => gp247_language_render('admin.banner.url'),
            'sort'   => gp247_language_render('admin.banner.sort'),
            'status' => gp247_language_render('admin.banner.status'),
            'click'  => gp247_language_render('admin.banner.click'),
            'target' => gp247_language_render('admin.banner.target'),
            'type'   => gp247_language_render('admin.banner.type'),
            'action' => gp247_language_render('action.title'),
        ];

        $sort_order = gp247_clean(request('sort_order') ?? 'id_desc');
        $keyword    = gp247_clean(request('keyword') ?? '');
        $arrSort = [
            'id__desc' => gp247_language_render('filter_sort.id_desc'),
            'id__asc' => gp247_language_render('filter_sort.id_asc'),
        ];
        $dataSearch = [
            'keyword'    => $keyword,
            'sort_order' => $sort_order,
            'arrSort'    => $arrSort,
        ];
        $dataTmp = FrontBanner::getBannerListAdmin($dataSearch, session('adminStoreId'));

        $dataTr = [];
        foreach ($dataTmp as $key => $row) {
            $dataMap = [
                'image' => gp247_image_render($row->getThumb(), '', '50px', 'Banner'),
                'title' => $row['title'],
                'url' => $row['url'],
                'sort' => $row['sort'],
                'status' => $row['status'] ? '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200">ON</span>' : '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200">OFF</span>',
                'click' => number_format($row['click']),
                'target' => $row['target'],
                'type' => $this->dataType[$row['type']]??'N/A',
                'action' => '
                    <a href="' . gp247_route_admin('vendor_admin_banner.edit', ['id' => $row['id'] ? $row['id'] : 'not-found-id']) . '"><span title="' . gp247_language_render('action.edit') . '" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-sm transition text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/40"><i class="fa fa-edit"></i></span></a>&nbsp;
                  <span onclick="deleteItem(\'' . $row['id'] . '\'); return false;"  title="' . gp247_language_render('action.delete') . '" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-sm transition text-red-600 hover:bg-red-50 dark:hover:bg-red-900/40"><i class="fas fa-trash-alt"></i></span>
                  ',
            ];
            $arrAction = [
                '<a href="' . gp247_route_admin('vendor_admin_banner.edit', ['id' => $row['id'], 'banner' => request('banner')]) . '"  class="' . $this->actionItemClass() . '"><span title="' . gp247_language_render('action.edit') . '"><i class="fa fa-edit"></i> '.gp247_language_render('action.edit').'</span></a>',
                ];
                $arrAction[] = '<a href="#" onclick="deleteItem(\'' . $row['id'] . '\'); return false;"  title="' . gp247_language_render('action.delete') . '" class="' . $this->actionItemClass() . '"><i class="fas fa-trash-alt"></i> '.gp247_language_render('action.remove').'</a>';
            $action = $this->procesListAction($arrAction);
            $dataMap['action'] = $action;
            $dataTr[] = $dataMap;
        }

        $data['listTh'] = $listTh;
        $data['dataTr'] = $dataTr;
        $data['pagination'] = $dataTmp->appends(request()->except(['_token', '_pjax']))->links($this->templatePathAdmin.'component.pagination');
        $data['resultItems'] = gp247_language_render('admin.result_item', ['item_from' => $dataTmp->firstItem(), 'item_to' => $dataTmp->lastItem(), 'total' =>  $dataTmp->total()]);

        //menuRight
        $data['menuRight'][] = '<a href="' . gp247_route_admin('vendor_admin_banner.create') . '" class="inline-flex items-center justify-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium transition bg-green-600 text-white hover:bg-green-700" title="New" id="button_create_new">
        <i class="fa fa-plus" title="'.gp247_language_render('action.add').'"></i>
                           </a>';
        //=menuRight

        
        return view($this->plugin->appPath.'::Admin.screen.vendor.list')
            ->with($data);
    }

/**
 * Form create new order in admin
 * @return [type] [description]
 */
    public function create()
    {
        $data = [
            'title' => gp247_language_render('admin.banner.add_new'),
            'subTitle' => '',
            'icon' => 'fa fa-plus',
            'banner' => [],
            'arrTarget' => $this->arrTarget,
            'dataType' => $this->dataType,
            'url_action' => gp247_route_admin('vendor_admin_banner.create'),
        ];
        return view($this->plugin->appPath.'::Admin.screen.vendor.banner')
            ->with($data);
    }

/**
 * Post create new order in admin
 * @return [type] [description]
 */
    public function postCreate()
    {
        $data = request()->all();
        $dataOrigin = request()->all();
        $validator = Validator::make($dataOrigin, [
            'sort' => 'numeric|min:0',
            'email' => 'email|nullable',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $dataInsert = [
            'image'    => $data['image'],
            'url'      => $data['url'],
            'title'    => $data['title'],
            'html'     => $data['html'],
            'type'     => $data['type'] ?? 0,
            'target'   => $data['target'],
            'status'   => empty($data['status']) ? 0 : 1,
            'sort'     => (int) $data['sort'],
        ];

        $banner = FrontBanner::createBannerAdmin($dataInsert);

        $shopStore        = [session('adminStoreId')];
        $banner->stores()->detach();
        $banner->stores()->attach($shopStore);
        
        return redirect()->route('vendor_admin_banner.index')->with('success', gp247_language_render('action.create_success'));

    }

    /**
     * Form edit
     */
    public function edit($id)
    {
        $banner = FrontBanner::getBannerAdmin($id, session('adminStoreId'));

        if (!$banner) {
            return redirect()->route('vendor_admin.data_not_found')->with(['url' => url()->full()]);
        }

        $data = [
            'title'             => gp247_language_render('action.edit'),
            'subTitle'          => '',
            'title_description' => '',
            'icon'              => 'fa fa-edit',
            'arrTarget'         => $this->arrTarget,
            'dataType'          => $this->dataType,
            'banner'            => $banner,
            'url_action'        => gp247_route_admin('vendor_admin_banner.edit', ['id' => $banner['id']]),
        ];
        return view($this->plugin->appPath.'::Admin.screen.vendor.banner')
            ->with($data);
    }

    /*
     * update status
     */
    public function postEdit($id)
    {
        $banner = FrontBanner::getBannerAdmin($id, session('adminStoreId'));
        if (!$banner) {
            return redirect()->route('vendor_admin.data_not_found')->with(['url' => url()->full()]);
        }

        $data = request()->all();
        $dataOrigin = request()->all();
        $validator = Validator::make($dataOrigin, [
            'sort' => 'numeric|min:0',
            'email' => 'email|nullable',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        //Edit
        $dataUpdate = [
            'image'    => $data['image'],
            'url'      => $data['url'],
            'title'    => $data['title'],
            'html'     => $data['html'],
            'type'     => $data['type'] ?? 0,
            'target'   => $data['target'],
            'status'   => empty($data['status']) ? 0 : 1,
            'sort'     => (int) $data['sort'],
        ];
        $banner->update($dataUpdate);

        $shopStore        = [session('adminStoreId')];
        $banner->stores()->detach();
        $banner->stores()->attach($shopStore);

        return redirect()->route('vendor_admin_banner.index')->with('success', gp247_language_render('action.edit_success'));

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
            $arrDontPermission = [];
            foreach ($arrID as $key => $id) {
                if(!$this->checkPermisisonItem($id)) {
                    $arrDontPermission[] = $id;
                }
            }
            if (count($arrDontPermission)) {
                return response()->json(['error' => 1, 'msg' => gp247_language_render('admin.remove_dont_permisison') . ': ' . json_encode($arrDontPermission)]);
            }

            FrontBanner::destroy($arrID);
            return response()->json(['error' => 0, 'msg' => '']);
        }
    }

    /**
     * Check permisison item
     */
    public function checkPermisisonItem($id) {
        return FrontBanner::getBannerAdmin($id, session('adminStoreId'));
    }

}
