<?php

namespace App\GP247\Plugins\MultiVendor\Admin\Livewire\Vendor;

use App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue;

/**
 * Pro-feature gateway for the vendor area.
 *
 * The vendor sidebar always lists the Pro screens; when one is not open on this
 * marketplace the item points here. A shop owner is not the person who buys the
 * plugin, so this screen never shows a price or a buy button — it says the
 * feature belongs to the marketplace's Pro edition and suggests asking the
 * marketplace. When the feature is open, it redirects to the real screen.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-upgrade-funnel
 * @aidlc-adr multi-vendor_pro-upgrade-funnel
 */
class VendorProGateway extends VendorAdminComponent
{
    /** @var string Gateway slug from the route (e.g. kyc). */
    public string $feature = '';

    /**
     * @param string $feature
     * @return void
     */
    public function mount(string $feature = ''): void
    {
        $this->feature = $feature;

        $screen = ProFeatureCatalogue::screen($feature);
        if ($screen !== null
            && $screen['audience'] === ProFeatureCatalogue::VENDOR
            && ProFeatureCatalogue::isOpen($screen)) {
            $this->redirect(gp247_route_admin($screen['route']), navigate: true);
        }
    }

    protected function pageTitle(): string
    {
        return gp247_language_render('multi_vendor.pro.heading');
    }

    protected function pageIcon(): string
    {
        return 'fas fa-crown';
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        $screen = ProFeatureCatalogue::screen($this->feature);
        $screen = $screen !== null && $screen['audience'] === ProFeatureCatalogue::VENDOR ? $screen : null;

        return $this->renderInShell('Plugins/MultiVendor::Admin.screen.vendor.livewire.pro_gateway', [
            'audience' => ProFeatureCatalogue::VENDOR,
            'featureTitle' => $screen ? gp247_language_render($screen['title']) : '',
            'featureDesc' => $screen ? gp247_language_render(ProFeatureCatalogue::descKey($screen['flag'])) : '',
            'features' => ProFeatureCatalogue::forAudience(ProFeatureCatalogue::VENDOR),
        ]);
    }
}
