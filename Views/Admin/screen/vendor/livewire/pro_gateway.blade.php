{{--
    Pro-feature gateway page, vendor side. Same block in the vendor voice: no
    price, no buy button — the shop owner is not the person who buys the plugin.

    @aidlc-unit multi-vendor-pro
    @aidlc-story US-multi-vendor-pro-upgrade-funnel

    Variables: $audience, $featureTitle, $featureDesc, $features.
--}}
<div class="py-6" data-testid="multi-vendor-pro-gateway-vendor">
    @include('Plugins/MultiVendor::Admin.partials.pro_upsell', [
        'audience'     => $audience,
        'variant'      => 'screen',
        'featureTitle' => $featureTitle,
        'featureDesc'  => $featureDesc,
        'features'     => $features,
    ])
</div>
