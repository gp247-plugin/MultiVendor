<?php
namespace App\GP247\Plugins\MultiVendor\Admin\Models;


use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use GP247\Core\Models\AdminStore;

class AdminMoneyProcess extends Model
{
    use \GP247\Core\Models\UuidTrait;

    public $table = 'vendor_money_process';
    protected $guarded = [];
    protected $connection = GP247_DB_CONNECTION;

    public function store()
    {
        return $this->belongsTo(AdminStore::class, 'store_id', 'id');
    }

    protected static function boot()
    {
        parent::boot();
        // before delete() method call this
        static::deleting(function ($model) {
            //
        });

        //Uuid
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = gp247_generate_id();
            }
        });
    }


    public function uninstall()
    {
        if (Schema::hasTable($this->table)) {
            Schema::drop($this->table);
        }
    }

    public function install()
    {
        $this->uninstall();

        Schema::create($this->table, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('content', 255)->nullable();
            $table->string('comment', 255)->nullable();
            $table->uuid('store_id')->index();
            $table->decimal('total_sum', 15, 2)->default(0);
            $table->integer('order_count')->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->integer('commission_rate')->default(0);
            $table->string('currency')->index();
            $table->date('date_process')->index();
            $table->string('status', 50)->default('processing')->comment('processing,pending,canceled,done')->index();
            $table->date('date_pay')->nullable()->index();
            $table->string('payout_method', 50)->nullable();
            $table->string('payout_account', 255)->nullable()->comment('Masked snapshot of the vendor payout account at processing time');
            $table->string('payout_reference', 150)->nullable()->comment('Bank/PayPal transaction reference entered when marked done');
            $table->string('paid_by', 50)->nullable()->comment('Admin id who marked the row done');
            // S3-1: period | clawback | refund | reversal; adjustments point at their period row.
            $table->string('kind', 20)->default('period')->index();
            $table->uuid('parent_id')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Upgrade an existing ledger in place (S1-5, idempotent — safe to run on every
     * plugin update, per the public-release migration policy):
     *   - total_sum / amount: integer → decimal(15,2) (values are preserved; the
     *     1.0 floor() had already dropped fractions, nothing to backfill)
     *   - payout_method / payout_account / payout_reference / paid_by: added when missing
     * Never drops the table (unlike install()).
     */
    public static function upgradeSchema(): void
    {
        $model = new static();
        $schema = Schema::connection($model->getConnectionName());
        $table = $model->getTable();
        if (!$schema->hasTable($table)) {
            return;
        }
        foreach (['total_sum', 'amount'] as $col) {
            if ($schema->hasColumn($table, $col) && strtolower((string) $schema->getColumnType($table, $col)) !== 'decimal') {
                $schema->table($table, function (Blueprint $t) use ($col) {
                    $t->decimal($col, 15, 2)->default(0)->change();
                });
            }
        }
        $schema->table($table, function (Blueprint $t) use ($schema, $table) {
            if (!$schema->hasColumn($table, 'payout_method')) {
                $t->string('payout_method', 50)->nullable();
            }
            if (!$schema->hasColumn($table, 'payout_account')) {
                $t->string('payout_account', 255)->nullable();
            }
            if (!$schema->hasColumn($table, 'payout_reference')) {
                $t->string('payout_reference', 150)->nullable();
            }
            if (!$schema->hasColumn($table, 'paid_by')) {
                $t->string('paid_by', 50)->nullable();
            }
            // S3-1 clawback: row kind + link to the period row an adjustment belongs to.
            if (!$schema->hasColumn($table, 'kind')) {
                $t->string('kind', 20)->default('period')->index();
            }
            if (!$schema->hasColumn($table, 'parent_id')) {
                $t->uuid('parent_id')->nullable()->index();
            }
        });
    }

}
