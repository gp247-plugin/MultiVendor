<?php

namespace App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor;

use App\GP247\Plugins\MultiVendor\Admin\Models\AdminVendorOrder;
use App\GP247\Plugins\MultiVendor\Dispute\Dispute;
use App\GP247\Plugins\MultiVendor\Models\VendorOrderShipment;
use App\GP247\Plugins\MultiVendor\Orders\VendorOrderPolicy;
use GP247\Core\Models\AdminCountry;
use GP247\Shop\Models\ShopAttributeGroup;
use GP247\Shop\Models\ShopOrderStatus;
use GP247\Shop\Models\ShopPaymentStatus;
use GP247\Shop\Models\ShopShippingStatus;
use Illuminate\Contracts\View\View;

/**
 * Vendor order detail — view one order and fulfil it (Pha 2 Livewire migration of
 * VendorOrderController::edit + postOrderUpdate, extended by S1-3). The order is
 * loaded scoped to the signed-in vendor's own store, so a vendor may only ever see
 * and touch their own store's orders.
 *
 * What a vendor can write here (S1-3, US-multi-vendor-pro-vendor-order-fulfillment):
 *   - shipping_status (inline, as in 1.0 — now also logged to the order history)
 *   - order status, ONLY along VendorOrderPolicy::allowedTransitions() and ONLY
 *     through ShopOrder::changeStatus() (the core seam: restock/history/events/
 *     finish_date) — never a plain column write
 *   - shipment details (carrier / tracking code / note) in the plugin's own table
 * Cancel / refund / re-open stay with the marketplace admin.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-vendor-admin-livewire
 * @aidlc-story US-multi-vendor-pro-vendor-order-fulfillment
 * @aidlc-adr multi-vendor_admin-livewire-migration
 * @aidlc-adr multi-vendor_vendor-order-fulfillment-seam
 */
class VendorOrderDetail extends VendorAdminComponent
{
    /**
     * Order columns a vendor may edit inline from this screen. Carried verbatim
     * from VendorOrderController::UPDATABLE_ORDER_FIELDS — only the shipping
     * status is editable here (security.md); the Livewire form binds exactly this
     * one field, so the browser can never name another column to write.
     */
    private const UPDATABLE_ORDER_FIELDS = ['shipping_status'];

    /** Id of the order being viewed (scalar; the model is reloaded each render). */
    public string $orderId = '';

    /** Inline shipping-status form field. */
    public string $shipping_status = '';

    /** Target order status picked by the vendor (only policy-allowed ids are offered). */
    public string $order_status = '';

    /** Shipment form fields (plugin table vendor_order_shipment). */
    public string $carrier = '';
    public string $tracking_code = '';
    public string $shipment_note = '';

    /**
     * Load the order scoped to the vendor's store; redirect to the shared
     * data-not-found screen when the order is not owned (same guard the controller
     * ran via checkPermisisonItem before rendering).
     *
     * @param int|string $id
     * @return \Illuminate\Http\RedirectResponse|null
     */
    public function mount($id)
    {
        // WHY reuse checkOrderAdmin: it counts by (id + store_id = adminStoreId),
        // the exact ownership check the controller used — a vendor never resolves
        // another store's order id.
        if (!(new AdminVendorOrder)->checkOrderAdmin($id)) {
            return redirect()->route('vendor_admin.data_not_found')->with(['url' => url()->full()]);
        }

        $order = AdminVendorOrder::getOrderAdmin($id, $this->vendorStoreId());
        if (!$order) {
            return redirect()->route('vendor_admin.data_not_found')->with(['url' => url()->full()]);
        }

        $this->orderId = (string) $order->id;
        $this->shipping_status = (string) $order->shipping_status;
        $this->order_status = (string) $order->status;

        $shipment = VendorOrderShipment::find($this->orderId);
        $this->carrier = (string) ($shipment->carrier ?? '');
        $this->tracking_code = (string) ($shipment->tracking_code ?? '');
        $this->shipment_note = (string) ($shipment->note ?? '');

        return null;
    }

    /**
     * Persist the inline shipping-status change — the Livewire equivalent of
     * VendorOrderController::postOrderUpdate. Same scope (id + store_id), same
     * single writable column, same value cleaning; postOrderUpdate had no history,
     * event or stock side-effect (a plain column write) and neither does this.
     *
     * @return void
     */
    public function updateStatus(): void
    {
        // WHY the allow-list still guards here: it names the ONLY column this
        // screen writes, matching the controller's UPDATABLE_ORDER_FIELDS contract
        // even though the form binds a single field.
        $code = 'shipping_status';
        if (!in_array($code, self::UPDATABLE_ORDER_FIELDS, true)) {
            $this->notify('error', 'Field not allowed: '.$code);

            return;
        }

        // WHY re-check ownership on write: the store scope is the vendor fence, not
        // just a display filter — never trust the mounted id alone at write time.
        $order = AdminVendorOrder::where('id', $this->orderId)
            ->where('store_id', $this->vendorStoreId())
            ->first();
        if (!$order) {
            $this->notify('error', gp247_language_render('vendor_admin.data_not_found_detail', ['msg' => 'order#'.$this->orderId]));

            return;
        }

        if (!VendorOrderPolicy::canEditShipping($order)) {
            $this->shipping_status = (string) $order->shipping_status;
            $this->notify('error', gp247_language_render('multi_vendor.order.status_locked'));

            return;
        }

        $value = gp247_clean((string) $this->shipping_status);
        $old = (string) $order->shipping_status;
        $order->update([$code => $value]);
        // S1-3: leave a trace for the marketplace admin (1.0 wrote the column silently).
        if ($old !== $value) {
            $order->addOrderHistory([
                'order_id' => $order->id,
                'content' => gp247_language_render('multi_vendor.order.history_shipping', ['email' => $this->vendorEmail(), 'from' => $old, 'to' => $value]),
                'admin_id' => 0,
                'customer_id' => 0,
                'order_status_id' => (int) $order->status,
            ]);
        }

        $this->notify('success', gp247_language_render('action.update_success'));
    }

    /**
     * Move the order along one policy-allowed transition (S1-3). The write is the
     * core seam ShopOrder::changeStatus() — restock, history, events and the
     * finish_date payout hook all happen there; a refused transition (seam returns
     * an i18n key) is shown to the vendor and the status stays unchanged.
     *
     * @return void
     */
    public function changeOrderStatus(): void
    {
        $order = AdminVendorOrder::where('id', $this->orderId)
            ->where('store_id', $this->vendorStoreId())
            ->first();
        if (!$order) {
            $this->notify('error', gp247_language_render('vendor_admin.data_not_found_detail', ['msg' => 'order#'.$this->orderId]));

            return;
        }

        $to = (int) $this->order_status;
        if ($to === (int) $order->status) {
            return;
        }
        if (!VendorOrderPolicy::canTransition($order, $to)) {
            $this->order_status = (string) $order->status;
            $this->notify('error', gp247_language_render('multi_vendor.order.transition_not_allowed'));

            return;
        }

        $from = (int) $order->status;
        $error = $order->changeStatus($to, [
            'content' => gp247_language_render('multi_vendor.order.history_status', ['email' => $this->vendorEmail(), 'from' => $from, 'to' => $to]),
            'admin_id' => 0,
        ]);
        if ($error !== null) {
            $this->order_status = (string) $order->status;
            $this->notify('error', gp247_language_render($error));

            return;
        }

        $this->order_status = (string) $to;
        $this->notify('success', gp247_language_render('action.update_success'));
    }

    /**
     * Save carrier / tracking code / note for this order (plugin table), and log a
     * history line so the marketplace admin sees the tracking on the core order screen.
     *
     * @return void
     */
    public function saveShipment(): void
    {
        $order = AdminVendorOrder::where('id', $this->orderId)
            ->where('store_id', $this->vendorStoreId())
            ->first();
        if (!$order) {
            $this->notify('error', gp247_language_render('vendor_admin.data_not_found_detail', ['msg' => 'order#'.$this->orderId]));

            return;
        }
        if (!VendorOrderPolicy::canEditShipping($order)) {
            $this->notify('error', gp247_language_render('multi_vendor.order.status_locked'));

            return;
        }

        $this->validate([
            'carrier' => 'nullable|string|max:100',
            'tracking_code' => 'nullable|string|max:100',
            'shipment_note' => 'nullable|string|max:255',
        ]);

        $carrier = gp247_clean(trim($this->carrier));
        $code = gp247_clean(trim($this->tracking_code));
        VendorOrderShipment::put($this->orderId, [
            'carrier' => $carrier,
            'tracking_code' => $code,
            'note' => gp247_clean(trim($this->shipment_note)),
            'shipped_at' => $code !== '' ? now() : null,
            'vendor_user_id' => (string) (auth()->guard('vendor')->id() ?? ''),
        ]);
        $order->addOrderHistory([
            'order_id' => $order->id,
            'content' => gp247_language_render('multi_vendor.order.history_shipment', ['email' => $this->vendorEmail(), 'carrier' => $carrier, 'code' => $code]),
            'admin_id' => 0,
            'customer_id' => 0,
            'order_status_id' => (int) $order->status,
        ]);

        $this->notify('success', gp247_language_render('multi_vendor.shipment.saved'));
    }

    /** Signed-in vendor e-mail for history lines. */
    private function vendorEmail(): string
    {
        return (string) (auth()->guard('vendor')->user()->email ?? 'vendor');
    }

    /**
     * Order-status id => label map (read-only display).
     *
     * @return array<int|string, string>
     */
    /** S3-3 dispute panel state. */
    public string $disputeAmount = '';
    public string $disputeNote = '';

    /**
     * S3-3: the vendor accepts the customer's dispute and refunds $disputeAmount
     * (≤ refundable) right away — the money moves on the order ledger via the service.
     */
    public function acceptDispute(): void
    {
        $this->validate(['disputeAmount' => 'required|numeric|min:0.01', 'disputeNote' => 'nullable|string|max:1000']);
        $dispute = $this->liveDispute();
        $ok = $dispute !== null && Dispute::vendorAccept($dispute, (float) $this->disputeAmount, gp247_clean(trim($this->disputeNote)));
        $this->disputeAmount = '';
        $this->disputeNote = '';
        $this->notify($ok ? 'success' : 'error', gp247_language_render($ok ? 'multi_vendor.dispute.vendor_accepted' : 'multi_vendor.dispute.resolve_failed'));
    }

    /**
     * S3-3: the vendor refuses with a reason — the dispute escalates to the marketplace.
     */
    public function rejectDispute(): void
    {
        $this->validate(['disputeNote' => 'required|string|min:'.Dispute::MIN_NOTE.'|max:1000']);
        $dispute = $this->liveDispute();
        $ok = $dispute !== null && Dispute::vendorReject($dispute, gp247_clean(trim($this->disputeNote)));
        $this->disputeNote = '';
        $this->notify($ok ? 'success' : 'error', gp247_language_render($ok ? 'multi_vendor.dispute.vendor_rejected' : 'multi_vendor.dispute.resolve_failed'));
    }

    /** The order's dispute only when it belongs to this vendor's store (fence). */
    private function liveDispute(): ?object
    {
        $dispute = Dispute::forOrder($this->orderId);

        return $dispute !== null && (string) $dispute->store_id === (string) $this->vendorStoreId() ? $dispute : null;
    }

    public function statusOrder(): array
    {
        return ShopOrderStatus::getIdAll();
    }

    /**
     * Payment-status id => label map (read-only display).
     *
     * @return array<int|string, string>
     */
    public function statusPayment(): array
    {
        return ShopPaymentStatus::getIdAll();
    }

    /**
     * Shipping-status id => label map (also the options of the inline form).
     *
     * @return array<int|string, string>
     */
    public function statusShipping(): array
    {
        return ShopShippingStatus::getIdAll();
    }

    /**
     * @return View
     */
    public function render(): View
    {
        // Reload each render so the lines relation is present and the view reflects
        // the just-saved shipping status (mirrors the controller's fresh read).
        $order = AdminVendorOrder::getOrderAdmin($this->orderId, $this->vendorStoreId());

        $allowed = $order ? VendorOrderPolicy::allowedTransitions((int) $order->status) : [];

        return $this->renderInShell('Plugins/MultiVendor::Admin.screen.vendor.livewire.order_detail', [
            'order'           => $order,
            'allowedStatuses' => $allowed,
            'canEditShipping' => $order ? VendorOrderPolicy::canEditShipping($order) : false,
            'printUrl'        => gp247_route_admin('vendor_admin_order.print', ['id' => $this->orderId]),
            'statusOrder'     => $this->statusOrder(),
            'statusPayment'   => $this->statusPayment(),
            'statusShipping'  => $this->statusShipping(),
            'dataTotal'       => AdminVendorOrder::getOrderTotal($this->orderId),
            'attributesGroup' => ShopAttributeGroup::pluck('name', 'id')->all(),
            'country'         => AdminCountry::getCodeAll(),
            // S3-3
            'dispute'         => Dispute::enabled() ? $this->liveDispute() : null,
            'disputeRefundable' => $order ? Dispute::refundable($order) : 0.0,
        ]);
    }

    /** @return string */
    protected function pageTitle(): string
    {
        return gp247_language_render('order.order_detail');
    }

    /** @return string */
    protected function pageIcon(): string
    {
        return 'fa fa-file-text-o';
    }
}
