<?php

namespace App\GP247\Plugins\MultiVendor\Livewire;

use App\GP247\Plugins\MultiVendor\AppConfig;
use App\GP247\Plugins\MultiVendor\Notifications\VendorNotifier;
use App\GP247\Plugins\MultiVendor\Dispute\Dispute;
use App\GP247\Plugins\MultiVendor\Kyc\Kyc;
use App\GP247\Plugins\MultiVendor\Orders\VendorOrderPolicy;
use App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue;
use App\GP247\Plugins\MultiVendor\Tier\Tier;
use App\GP247\Plugins\MultiVendor\VendorPlugins\VendorPlugins;
use GP247\Core\AdminShell\Infrastructure\ConfigForm;

/**
 * Marketplace-wide settings for MultiVendor (admin_config, global store).
 *
 * Replaces the v1 `AdminRootVendorConfigController` screen, which edited these
 * same rows through x-editable/iCheck + a POST to `admin_config_global.update`.
 * That route no longer exists in gp247/core 2.x (the global settings screen was
 * itself replaced by this ConfigForm base), so the v1 screen could not save at
 * all — it failed while merely resolving the route name.
 *
 * Access is decided by URI+method (ADR-001 Layer-2) against this component's
 * route, so no permission slug is declared here.
 */
class RootConfigForm extends ConfigForm
{
    /**
     * WHY seed here too: ConfigForm::save() UPDATEs existing rows only, so on a
     * site updated by plain file replacement (no gp247:ext-update run) the four
     * notification flags would have no row and could never be switched off.
     * The seed is insertOrIgnore — idempotent and cheap.
     */
    public function mount(): void
    {
        AppConfig::seedNotificationSettings();
        // A site installed before the convergence fix (mod 20260920T182637) — or
        // reinstalled by an older copy of this plugin — has lost the Pro menu
        // items. This is the screen the marketplace owner does reach, so repair
        // from here; the call costs one count query when nothing is missing.
        AppConfig::repairIfNeeded();
        // S2-1: one opt-in flag per eligible plugin (default OFF); the set follows what
        // is installed. Asked through the free contract — a no-op without the paid plugin.
        VendorPlugins::seedSettings();
        parent::mount();
    }

    /**
     * The plugin seeds its keys with an empty group (see AppConfig::install()).
     *
     * @return string
     */
    protected function group(): string
    {
        return '';
    }

    /**
     * Settings that only do something with the Pro edition, and the feature each
     * one belongs to. They are SHOWN in both editions — locked on Free — because a
     * hidden switch cannot make anyone want the feature behind it
     * (US-multi-vendor-pro-upgrade-funnel).
     *
     * @return array<string, string> config key => Tier flag
     */
    private function proOnlyKeys(): array
    {
        return [
            'MultiVendor_quick_order' => Tier::F_QUICK_ORDER,
            VendorNotifier::configKey(VendorNotifier::PENDING_REVIEW) => Tier::F_MAIL_PENDING_REVIEW,
            VendorNotifier::configKey(VendorNotifier::VENDOR_APPROVED) => Tier::F_MAIL_VENDOR_APPROVED,
            VendorNotifier::configKey(VendorNotifier::PAYOUT_DONE) => Tier::F_MAIL_PAYOUT_DONE,
            VendorNotifier::configKey(VendorNotifier::PAYOUT_CLAWBACK) => Tier::F_CLAWBACK,
            VendorOrderPolicy::CONFIG_KEY => Tier::F_ORDER_FULFILLMENT,
            Kyc::CONFIG_KEY => Tier::F_KYC,
            VendorNotifier::configKey(VendorNotifier::DISPUTE) => Tier::F_DISPUTE,
            Dispute::CONFIG_WINDOW_DAYS => Tier::F_DISPUTE,
            Dispute::CONFIG_VENDOR_DAYS => Tier::F_DISPUTE,
        ];
    }

    /**
     * Only this plugin's own keys — the empty group also holds every other
     * store-level setting, which has its own screens. The same set in both
     * editions; what differs on Free is which of them are locked.
     *
     * @return array<int, string>
     */
    protected function keys(): array
    {
        return array_merge($this->allKeys(), $this->vendorPluginKeys());
    }

    /**
     * On a Free marketplace every Pro-only setting stays visible but locked, with
     * the feature it belongs to and a link to the upgrade gateway.
     *
     * @return array<string, array{hint: string, url: string, label: string}>
     */
    protected function lockedKeys(): array
    {
        $locked = [];
        foreach ($this->proOnlyKeys() as $key => $flag) {
            if (Tier::allows($flag)) {
                continue;
            }
            $locked[$key] = [
                'hint' => gp247_language_render('multi_vendor.pro.setting_locked', ['feature' => gp247_language_render(ProFeatureCatalogue::titleKey($flag))]),
                'url' => ProFeatureCatalogue::gatewayUrl(ProFeatureCatalogue::ROOT, 'report'),
                'label' => gp247_language_render('multi_vendor.pro.cta'),
            ];
        }

        return $locked;
    }

    /**
     * S2-1: the per-plugin "vendors may configure" flags (Pro), one per eligible plugin.
     *
     * @return array<int, string>
     */
    private function vendorPluginKeys(): array
    {
        return VendorPlugins::settingKeys();
    }

    /** Every marketplace key regardless of edition. */
    private function allKeys(): array
    {
        return [
            'MultiVendor_commission',
            'MultiVendor_allow_register',
            'MultiVendor_vendor_auto_approve',
            'MultiVendor_product_auto_approve',
            'MultiVendor_quick_order',
            VendorNotifier::configKey(VendorNotifier::ORDER_CREATED),
            VendorNotifier::configKey(VendorNotifier::PENDING_REVIEW),
            VendorNotifier::configKey(VendorNotifier::VENDOR_APPROVED),
            VendorNotifier::configKey(VendorNotifier::PAYOUT_DONE),
            VendorNotifier::configKey(VendorNotifier::PAYOUT_CLAWBACK),
            VendorOrderPolicy::CONFIG_KEY,
            Kyc::CONFIG_KEY,
            VendorNotifier::configKey(VendorNotifier::DISPUTE),
            Dispute::CONFIG_WINDOW_DAYS,
            Dispute::CONFIG_VENDOR_DAYS,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function fieldTypes(): array
    {
        return [
            'MultiVendor_commission' => 'number',
            'MultiVendor_allow_register' => 'bool',
            'MultiVendor_vendor_auto_approve' => 'bool',
            'MultiVendor_product_auto_approve' => 'bool',
            'MultiVendor_quick_order' => 'bool',
            VendorNotifier::configKey(VendorNotifier::ORDER_CREATED) => 'bool',
            VendorNotifier::configKey(VendorNotifier::PENDING_REVIEW) => 'bool',
            VendorNotifier::configKey(VendorNotifier::VENDOR_APPROVED) => 'bool',
            VendorNotifier::configKey(VendorNotifier::PAYOUT_DONE) => 'bool',
            VendorNotifier::configKey(VendorNotifier::PAYOUT_CLAWBACK) => 'bool',
            VendorOrderPolicy::CONFIG_KEY => 'select',
            Kyc::CONFIG_KEY => 'bool',
            VendorNotifier::configKey(VendorNotifier::DISPUTE) => 'bool',
            Dispute::CONFIG_WINDOW_DAYS => 'number',
            Dispute::CONFIG_VENDOR_DAYS => 'number',
        ] + array_fill_keys($this->vendorPluginKeys(), 'bool');
    }

    /**
     * Preset labels for the vendor order scope (S1-3).
     *
     * @return array<string, array<string, string>>
     */
    protected function fieldOptions(): array
    {
        $options = [];
        foreach (VendorOrderPolicy::SCOPES as $scope) {
            $options[$scope] = gp247_language_render('multi_vendor.vendor_order_scope_'.$scope);
        }

        return [VendorOrderPolicy::CONFIG_KEY => $options];
    }

    /**
     * WHY: the commission is a percentage — the unit is not obvious from the
     * bare number field, and v1 printed a literal "%" next to it.
     *
     * @return array<string, string>
     */
    protected function fieldHints(): array
    {
        return [
            'MultiVendor_commission' => '%',
        ];
    }

    /**
     * @return string
     */
    protected function heading(): string
    {
        return gp247_language_render('multi_vendor.MultiVendor_config').' — '.Tier::label();
    }
}
