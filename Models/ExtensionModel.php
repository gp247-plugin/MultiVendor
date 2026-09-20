<?php
#App\GP247\Plugins\MultiVendor\Models\ExtensionModel.php
namespace App\GP247\Plugins\MultiVendor\Models;

use App\GP247\Plugins\MultiVendor\Models\VendorCategory;
use App\GP247\Plugins\MultiVendor\Models\VendorProductCategory;
use App\GP247\Plugins\MultiVendor\Models\VendorUser;
use App\GP247\Plugins\MultiVendor\Admin\Models\AdminMoneyProcess;
use App\GP247\Plugins\MultiVendor\Models\VendorOrderShipment;
use App\GP247\Plugins\MultiVendor\Models\VendorReviewLog;
use GP247\Core\Models\AdminStore;
use GP247\Shop\Models\ShopProduct;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class ExtensionModel
{

    public function uninstallExtension()
    {
        (new VendorCategory)->uninstall();
        (new VendorProductCategory)->uninstall();
        (new VendorUser)->uninstall();
        (new AdminMoneyProcess)->uninstall();
        (new VendorOrderShipment)->uninstall();
        (new VendorReviewLog)->uninstall();
        if (Schema::hasColumn(GP247_DB_PREFIX.'shop_order', 'finish_date'))
        {
            Schema::table(GP247_DB_PREFIX.'shop_order', function (Blueprint $table) {
                $table->dropColumn('finish_date');
            });
        }
    }

    public function installExtension()
    {
        (new VendorCategory)->install();
        (new VendorProductCategory)->install();
        (new VendorUser)->install();
        (new AdminMoneyProcess)->install();
        (new VendorOrderShipment)->install(); // re-entrant (S1-3)
        (new VendorReviewLog)->install(); // re-entrant (S1-4)
        if (!Schema::hasColumn(GP247_DB_PREFIX.'shop_order', 'finish_date'))
        {
            Schema::table(GP247_DB_PREFIX.'shop_order', function (Blueprint $table) {
                $table->date('finish_date')->nullable()->index()->comment('Date order status finish');
            });
        }
    }
}
