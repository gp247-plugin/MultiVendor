<?php

namespace App\GP247\Plugins\MultiVendor\Hooks;

use App\GP247\Plugins\MultiVendor\AppConfig;
use App\GP247\Plugins\MultiVendor\Dispute\Dispute;
use GP247\Shop\Models\ShopOrder;

/**
 * Storefront extension point renderer (S3-3): the dispute box under a
 * customer's order page. Registered in Provider against
 * `shop_order_detail_bottom`; the screen hands us ['order' => ShopOrder].
 *
 * Renders nothing on Free, for the marketplace's own (ROOT) orders, or when
 * no customer is signed in. Templates may override the view by placing
 * `hooks/order_dispute_box.blade.php` in their folder (gp247_plugin_process_view).
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-order-dispute
 * @aidlc-adr multi-vendor_order-dispute
 */
class OrderDetailHook
{
    public static function render(array $data = []): string
    {
        $order = $data['order'] ?? null;
        if (!($order instanceof ShopOrder) || empty($order->id) || !Dispute::enabled()) {
            return '';
        }
        if ((string) $order->store_id === '' || (string) $order->store_id === (string) GP247_STORE_ID_ROOT) {
            return '';
        }
        $customer = function_exists('customer') && customer()->check() ? customer()->user() : null;
        if ($customer === null) {
            return '';
        }
        $customerId = (string) $customer->id;
        $dispute = Dispute::forOrder((string) $order->id);
        if ($dispute !== null && (string) $dispute->status === Dispute::STATUS_OPEN) {
            Dispute::escalateOverdue();
            $dispute = $dispute->fresh();
        }

        $plugin = new AppConfig;
        $view = gp247_plugin_process_view($plugin->appPath, config('gp247-config.front.template_path', ''), 'hooks.order_dispute_box');
        if (!view()->exists($view)) {
            $view = 'Plugins/MultiVendor::hooks.order_dispute_box';
        }

        return view($view, [
            'order' => $order,
            'dispute' => $dispute,
            'ineligible' => Dispute::eligibility($order, $customerId),
            'refundable' => Dispute::refundable($order),
            'types' => Dispute::TYPES,
            'notice' => session('multi_vendor_dispute_notice'),
            'noticeType' => session('multi_vendor_dispute_notice_type', 'success'),
        ])->render();
    }
}
