<?php

namespace App\GP247\Plugins\MultiVendor\Admin\Controllers\Auth;

use App\GP247\Plugins\MultiVendor\Admin\Controllers\RootVendorController;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Registered;
use App\GP247\Plugins\MultiVendor\Admin\Models\AdminVendorUser;
use GP247\Core\Models\AdminCountry;
use GP247\Core\Models\AdminStore;
use Illuminate\Http\JsonResponse;
use App\GP247\Plugins\MultiVendor\Events\CreatingVendorUser;
use App\GP247\Plugins\MultiVendor\Events\CreatedVendorUser;

class RegisterController extends RootVendorController
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
     */

    // use RegistersUsers;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Form create new item in admin
     * @return [type] [description]
     */
    public function showRegister()
    {
        $data = [
            'title'             => gp247_language_render('multi_vendor.vendor_add'),
            'subTitle'          => '',
            'title_description' => '',
            'icon'              => 'fa fa-plus',
            'vendor'          => [],
            'countries'         => (new AdminCountry)->getCodeAll(),
            'url_action'        => gp247_route_admin('vendor.postRegister'),
        ];

        return view($this->plugin->appPath.'::Admin.auth.register')
            ->with($data);
    }


    /**
    * Post create new item in admin
    * @return [type] [description]
    */
    public function postRegister()
    {
        // S4-1: Free edition vendor quota — self-registration is refused the same
        // way the admin create form is (server-side).
        if (\App\GP247\Plugins\MultiVendor\Tier\Tier::quotaReached()) {
            return redirect()->back()->withInput()->with('error', gp247_language_render('multi_vendor.tier.quota_reached_register'));
        }

        $arraycountry = (new AdminCountry)->pluck('code')->toArray();

        $data = request()->all();
        $validate = [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'password' => 'required|string|confirmed|min:6',
            'email' => 'required|string|email|max:255|unique:"'.AdminVendorUser::class.'",email',
            'store_code' => 'required|string|max:20|unique:"'.AdminStore::class.'",code',
        ];
        $validate['address1'] = 'nullable|string|max:100';
        $validate['address2'] = 'nullable|string|max:100';
        $validate['postcode'] = 'nullable|min:5';
        $validate['country'] = 'nullable|string|min:2|in:'. implode(',', $arraycountry);
        $validate['phone'] = 'nullable';

        $messages = [
            'last_name.required'   => gp247_language_render('validation.required', ['attribute'=> gp247_language_render('multi_vendor.last_name')]),
            'first_name.required'  => gp247_language_render('validation.required', ['attribute'=> gp247_language_render('multi_vendor.first_name')]),
            'email.required'       => gp247_language_render('validation.required', ['attribute'=> gp247_language_render('multi_vendor.email')]),
            'password.required'    => gp247_language_render('validation.required', ['attribute'=> gp247_language_render('multi_vendor.password')]),
            'address1.required'    => gp247_language_render('validation.required', ['attribute'=> gp247_language_render('multi_vendor.address1')]),
            'address2.required'    => gp247_language_render('validation.required', ['attribute'=> gp247_language_render('multi_vendor.address2')]),
            'phone.required'       => gp247_language_render('validation.required', ['attribute'=> gp247_language_render('multi_vendor.phone')]),
            'country.required'     => gp247_language_render('validation.required', ['attribute'=> gp247_language_render('multi_vendor.country')]),
            'postcode.required'    => gp247_language_render('validation.required', ['attribute'=> gp247_language_render('multi_vendor.postcode')]),
            'email.email'          => gp247_language_render('validation.email', ['attribute'=> gp247_language_render('multi_vendor.email')]),
            'phone.regex'          => gp247_language_render('multi_vendor.phone_regex'),
            'postcode.min'         => gp247_language_render('validation.min', ['attribute'=> gp247_language_render('multi_vendor.postcode')]),
            'password.min'         => gp247_language_render('validation.min', ['attribute'=> gp247_language_render('multi_vendor.password')]),
            'password.confirmed'   => gp247_language_render('validation.confirmed', ['attribute'=> gp247_language_render('multi_vendor.password')]),
            'country.min'          => gp247_language_render('validation.min', ['attribute'=> gp247_language_render('multi_vendor.country')]),
            'first_name.max'       => gp247_language_render('validation.max', ['attribute'=> gp247_language_render('multi_vendor.first_name')]),
            'email.max'            => gp247_language_render('validation.max', ['attribute'=> gp247_language_render('multi_vendor.email')]),
            'address1.max'         => gp247_language_render('validation.max', ['attribute'=> gp247_language_render('multi_vendor.address1')]),
            'address2.max'         => gp247_language_render('validation.max', ['attribute'=> gp247_language_render('multi_vendor.address2')]),
            'last_name.max'        => gp247_language_render('validation.max', ['attribute'=> gp247_language_render('multi_vendor.last_name')]),
        ];

        $validator = Validator::make($data, $validate, $messages);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $data['password'] = bcrypt($data['password']);
        $data['email'] = strtolower($data['email']);

        $dataStore = [
            'code' => $data['store_code'],
            'language' => 'en',
            'currency' => 'usd',
        ];

        // If the vendor is not auto-approved, set the status to 0
        if (!gp247_config_global('MultiVendor_vendor_auto_approve')) {
            $dataStore['status'] = 0;
        }

        unset($data['password_confirmation']);;
        unset($data['store_code']);;
        $dataClean = gp247_clean($data, ['password']);

        CreatingVendorUser::dispatch($dataClean);
        
        try {
            \DB::connection(GP247_DB_CONNECTION)->beginTransaction();
            
            $store = AdminStore::create($dataStore);

            //Add config default for new store
            AdminStore::setUpDataDefault($store);

            $dataClean['store_id'] = $store->id;
            $vendor = AdminVendorUser::create($dataClean);
            CreatedVendorUser::dispatch($vendor);
            
            \DB::connection(GP247_DB_CONNECTION)->commit();

            \Auth::guard('vendor')->login($vendor);
            return redirect()->route('vendor_admin.home')->with('success', gp247_language_render('action.create_success'));

        } catch (\Exception $e) {
            \DB::connection(GP247_DB_CONNECTION)->rollback();
            gp247_report($e->getMessage());
            return redirect()->back()->with('error', gp247_language_render('action.create_error'));
        }

    }
    
}
