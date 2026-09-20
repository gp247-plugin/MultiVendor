{{--
    Pro-feature gateway page, marketplace-owner side. Reached only when the
    feature is not open (otherwise ProGateway::mount redirects to the real
    screen), so this page is always the "here is what Pro adds" page.

    @aidlc-unit multi-vendor-pro
    @aidlc-story US-multi-vendor-pro-upgrade-funnel

    Variables: $audience, $featureTitle, $featureDesc, $features.
--}}
<div class="py-6" data-testid="multi-vendor-pro-gateway-root">
    @include('Plugins/MultiVendor::Admin.partials.pro_upsell', [
        'audience'     => $audience,
        'variant'      => 'screen',
        'featureTitle' => $featureTitle,
        'featureDesc'  => $featureDesc,
        'features'     => $features,
    ])
</div>
