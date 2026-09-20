<?php
namespace App\GP247\Plugins\MultiVendor\Models;

use App\GP247\Plugins\MultiVendor\Models\VendorCategoryDescription;
use GP247\Shop\Models\ShopProduct;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use GP247\Core\Models\AdminStore;
use GP247\Core\Models\ModelTrait;

class VendorCategory extends Model
{
    use ModelTrait;
    use \GP247\Core\Models\UuidTrait;

    public $table = 'vendor_category';
    public $tableDescription = 'vendor_category_description';
    protected $guarded = [];
    protected $connection = GP247_DB_CONNECTION;

    protected  $gp247_store_info = 0; 

    public function products()
    {
        return $this->belongsToMany(ShopProduct::class, 'vendor_product_category', 'vendor_category_id', 'product_id');
    }

    public function store()
    {
        return $this->belongsTo(AdminStore::class, 'store_id', 'id');
    }

    public function descriptions()
    {
        return $this->hasMany(VendorCategoryDescription::class, 'vendor_category_id', 'id');
    }

    //Function get text description 
    public function getText() {
        return $this->descriptions()->where('lang', gp247_get_locale())->first();
    }
    public function getTitle() {
        return $this->getText()->title ?? '';
    }
    public function getDescription() {
        return $this->getText()->description ?? '';
    }
    public function getKeyword() {
        return $this->getText()->keyword ?? '';
    }
    //End  get text description

    protected static function boot()
    {
        parent::boot();
        // before delete() method call this
        static::deleting(function ($category) {
            //Delete category descrition
            $category->descriptions()->delete();
            $category->products()->detach();
        });

        //Uuid
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = gp247_generate_id();
            }
        });
    }

    /*
    *Get thumb
    */
    public function getThumb()
    {
        return gp247_image_get_path_thumb($this->image);
    }

    /*
    *Get image
    */
    public function getImage()
    {
        return gp247_image_get_path($this->image);
    }

    public function getUrl()
    {
        return route('MultiVendor_category.detail', ['alias' => $this->alias, 'storeId' => $this->store_id]);
    }

    /**
     * Set store id
     *
     */
    public function setStore($id) {
        $this->gp247_store_info = $id;
        return $this;
    }

    //Scort
    public function scopeSort($query, $sortBy = null, $sortOrder = 'asc')
    {
        $sortBy = $sortBy ?? 'sort';
        return $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * Get sub category detail
     *
     * @param   [type]$key        [$key description]
     * @param   [type]$type       [$type description]
     * @param   null  $storeCode  [$storeCode description]
     *
     * @return  [type]            [return description]
     */
    public function getDetail($key, $type = null, $storeId = null, $status = 1)
    {
        if (empty($key)) {
            return null;
        }
        $storeId = empty($storeId) ? config('app.storeId') : $storeId;
        $tableDescription = (new VendorCategoryDescription)->getTable();
        $category = $this
            ->leftJoin($tableDescription, $tableDescription . '.vendor_category_id', $this->getTable() . '.id')
            ->where($this->getTable() . '.store_id', $storeId)
            ->where($tableDescription . '.lang', gp247_get_locale());

        if ($type === null) {
            $category = $category->where($this->getTable() .'.id',  $key);
        } else {
            $category = $category->where($type, $key);
        }
        $category = $category->where($this->getTable() .'.status', $status);
        return $category->first();
    }
    


    /**
     * Start new process get data
     *
     * @return  new model
     */
    public function start() {
        return new VendorCategory;
    }

    /**
     * build Query
     */
    public function buildQuery() {
        $tableDescription = (new VendorCategoryDescription)->getTable();

        $dataSelect = $this->getTable().'.*, '.$tableDescription.'.*';

        //description
        $query = $this
            ->selectRaw($dataSelect)
            ->leftJoin($tableDescription, $tableDescription . '.vendor_category_id', $this->getTable() . '.id')
            ->where($tableDescription . '.lang', gp247_get_locale());
        //search keyword
        if ($this->gp247_keyword !='') {
            $query = $query->where(function ($sql) use($tableDescription){
                $sql->where($tableDescription . '.title', 'like', '%' . $this->gp247_keyword . '%')
                    ->orWhere($tableDescription . '.keyword', 'like', '%' . $this->gp247_keyword . '%')
                    ->orWhere($tableDescription . '.description', 'like', '%' . $this->gp247_keyword . '%');
            });
        }

        $storeId = $this->gp247_store_info ? $this->gp247_store_info : config('app.storeId');

        //Process store
        if (!empty($this->gp247_store_info)) {
            //If the store is specified or the default is not the primary store
            //Only get sub-category from eligible stores
            $tableStore = (new AdminStore)->getTable();
            $query = $query->join($tableStore, $tableStore . '.id', $this->getTable().'.store_id');
            $query = $query->where($this->getTable().'.store_id', $storeId);
        }
        //End store


        $query = $query->where($this->getTable().'.status', 1);

        if ($this->gp247_random) {
            $query = $query->inRandomOrder();
        } else {
            if (is_array($this->gp247_sort) && count($this->gp247_sort)) {
                foreach ($this->gp247_sort as  $rowSort) {
                    if(is_array($rowSort) && count($rowSort) == 2) {
                        $query = $query->sort($rowSort[0], $rowSort[1]);
                    }
                }
            }
        }

        return $query;
    }

    //==================================

    public function uninstall()
    {
        if (Schema::hasTable($this->table)) {
            Schema::drop($this->table);
        }

        if (Schema::hasTable($this->tableDescription)) {
            Schema::drop($this->tableDescription);
        }
    }

    public function install()
    {
        $this->uninstall();

        Schema::create($this->table, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('image', 255)->nullable();
            $table->string('alias', 120)->index();
            $table->tinyInteger('status')->default(0);
            $table->integer('sort')->default(0);
            $table->uuid('store_id')->index();
            $table->timestamps();
        });

        Schema::create($this->table.'_description', function (Blueprint $table) {
            $table->uuid('vendor_category_id');
            $table->string('lang', 10)->index();
            $table->string('title', 300)->nullable();
            $table->string('keyword', 200)->nullable();
            $table->string('description', 500)->nullable();
            $table->unique(['vendor_category_id', 'lang']);
        });
        
    }

}
