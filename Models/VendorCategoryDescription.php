<?php
namespace App\GP247\Plugins\MultiVendor\Models;

use Illuminate\Database\Eloquent\Model;

class VendorCategoryDescription extends Model
{
    protected $primaryKey = ['vendor_category_id', 'lang'];
    public $incrementing  = false;
    public $timestamps    = false;
    public $table = 'vendor_category_description';
    protected $connection = GP247_DB_CONNECTION;
    protected $guarded    = [];
}
