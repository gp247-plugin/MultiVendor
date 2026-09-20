<?php

namespace App\GP247\Plugins\MultiVendor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shipment details a vendor attaches to one of their orders (S1-3): carrier,
 * tracking code, note, shipped-at. One row per order (order_id is the key).
 *
 * WHY a plugin table: gp247/shop's order table has no tracking columns and the
 * plugin must not touch core schema (NFR-mv-no-core-front-change). The install is
 * re-entrant (Schema::hasTable) so it can run from install(), update() and any
 * later repair without dropping data — unlike the 1.0 drop-and-create models.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-vendor-order-fulfillment
 * @aidlc-adr multi-vendor_vendor-order-fulfillment-seam
 */
class VendorOrderShipment extends Model
{
    public $table = 'vendor_order_shipment';
    protected $primaryKey = 'order_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $connection = GP247_DB_CONNECTION;

    /** Create the table when missing; never drops an existing one. */
    public function install(): void
    {
        if (Schema::connection($this->connection)->hasTable($this->table)) {
            return;
        }
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->string('order_id', 50)->primary();
            $table->string('carrier', 100)->nullable();
            $table->string('tracking_code', 100)->nullable()->index();
            $table->string('note', 255)->nullable();
            $table->dateTime('shipped_at')->nullable();
            $table->string('vendor_user_id', 50)->nullable();
            $table->timestamps();
        });
    }

    public function uninstall(): void
    {
        if (Schema::connection($this->connection)->hasTable($this->table)) {
            Schema::connection($this->connection)->drop($this->table);
        }
    }

    /**
     * Upsert the shipment row of an order.
     *
     * @param array<string, mixed> $data carrier, tracking_code, note, shipped_at, vendor_user_id
     */
    public static function put(string $orderId, array $data): self
    {
        $row = static::find($orderId) ?? new static(['order_id' => $orderId]);
        $row->fill(array_intersect_key($data, array_flip(['carrier', 'tracking_code', 'note', 'shipped_at', 'vendor_user_id'])));
        $row->save();

        return $row;
    }
}
