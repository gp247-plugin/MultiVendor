<?php

namespace App\GP247\Plugins\MultiVendor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit row for one moderation decision (S1-4): who approved/rejected which
 * vendor store or vendor product, when, and why.
 *
 * WHY a plugin table: the decision and its reason belong to the marketplace, not
 * to core's store/product rows (which only carry the resulting flag). Keeping the
 * history lets the queue show "last rejection reason" when a product comes back
 * for review, and gives the marketplace an audit trail for disputes. Install is
 * re-entrant (Schema::hasTable) — never drops data on update.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-moderation-queue
 * @aidlc-adr multi-vendor_moderation-queue-service
 */
class VendorReviewLog extends Model
{
    public const TYPE_VENDOR = 'vendor';
    public const TYPE_PRODUCT = 'product';
    public const TYPE_KYC = 'kyc';   // S3-2: identity profile decisions (subject_id = store id)
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    public $table = 'vendor_review_log';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $connection = GP247_DB_CONNECTION;
    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = gp247_uuid();
            }
            if (empty($model->created_at)) {
                $model->created_at = date('Y-m-d H:i:s');
            }
        });
    }

    /** Create the table when missing; never drops an existing one. */
    public function install(): void
    {
        if (Schema::connection($this->connection)->hasTable($this->table)) {
            return;
        }
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('subject_type', 20)->index();
            $table->string('subject_id', 50)->index();
            $table->string('store_id', 50)->nullable()->index();
            $table->string('decision', 20);
            $table->text('reason')->nullable();
            $table->string('admin_id', 50)->nullable();
            $table->dateTime('created_at')->nullable()->index();
        });
    }

    public function uninstall(): void
    {
        if (Schema::connection($this->connection)->hasTable($this->table)) {
            Schema::connection($this->connection)->drop($this->table);
        }
    }

    /** Most recent rejection for a subject, or null. */
    public static function lastRejection(string $type, string $subjectId): ?self
    {
        return static::where('subject_type', $type)
            ->where('subject_id', $subjectId)
            ->where('decision', self::REJECTED)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
