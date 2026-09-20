<?php
namespace App\GP247\Plugins\MultiVendor\Admin\Controllers\Vendor;

use UniSharp\LaravelFilemanager\Controllers\LfmController;
class VendorUploadController extends LfmController
{

    public function __construct()
    {
        parent::__construct();
    }
        /**
     * Show the filemanager.
     *
     * @return mixed
     */
    public function show()
    {
        return view('Plugins/MultiVendor::Admin.screen.vendor.upload')
        ->withHelper($this->helper);
    }

}
