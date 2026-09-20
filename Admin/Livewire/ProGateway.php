<?php
#App\GP247\Plugins\MultiVendor\Admin\Livewire\ProGateway.php

namespace App\GP247\Plugins\MultiVendor\Admin\Livewire;

use App\GP247\Plugins\MultiVendor\Tier\ProFeatureCatalogue;
use GP247\Core\AdminShell\Infrastructure\GP247AdminComponent;
use Illuminate\Contracts\View\View;

/**
 * Pro-feature gateway for the marketplace owner (root admin).
 *
 * Every Pro item in the marketplace menu points here (`/pro/{feature}`), in both
 * editions. When the feature is open — the edition allows it and its real route
 * is registered — the gateway sends the browser straight to the real screen, so
 * installing Pro never requires touching the menu. When it is not, the gateway
 * explains the feature and lists everything Pro adds, with the upgrade call to
 * action: this is the buyer's screen.
 *
 * Mirrors MultiStore's ProGateway (ADR multi-store_free-pro-split).
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-upgrade-funnel
 * @aidlc-adr multi-vendor_pro-upgrade-funnel
 */
class ProGateway extends GP247AdminComponent
{
    /** @var string Gated like the Free store screens. */
    protected ?string $permission = 'admin_MultiVendor';

    /** @var string Gateway slug from the route (e.g. report). */
    public string $feature = '';

    /**
     * @param string $feature
     * @return void
     */
    public function mount(string $feature = ''): void
    {
        parent::mount();
        $this->feature = $feature;

        $screen = ProFeatureCatalogue::screen($feature);
        if ($screen !== null
            && $screen['audience'] === ProFeatureCatalogue::ROOT
            && ProFeatureCatalogue::isOpen($screen)) {
            $this->redirect(gp247_route_admin($screen['route']), navigate: true);
        }
    }

    /**
     * @return View
     */
    public function render(): View
    {
        $screen = ProFeatureCatalogue::screen($this->feature);
        $screen = $screen !== null && $screen['audience'] === ProFeatureCatalogue::ROOT ? $screen : null;

        return view('Plugins/MultiVendor::Admin.screen.root.livewire.pro_gateway', [
            'audience' => ProFeatureCatalogue::ROOT,
            'featureTitle' => $screen ? gp247_language_render($screen['title']) : '',
            'featureDesc' => $screen ? gp247_language_render(ProFeatureCatalogue::descKey($screen['flag'])) : '',
            'features' => ProFeatureCatalogue::forAudience(ProFeatureCatalogue::ROOT),
        ])->layout('gp247-admin::layouts.admin', [
            'title' => gp247_language_render('multi_vendor.pro.heading'),
        ]);
    }
}
