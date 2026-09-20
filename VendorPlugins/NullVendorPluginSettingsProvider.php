<?php

namespace App\GP247\Plugins\MultiVendor\VendorPlugins;

/**
 * No "vendors may configure plugins" feature installed: nothing to seed and no
 * per-plugin flags on the marketplace settings screen.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-paid-code-location
 * @aidlc-adr multi-vendor_pro-code-relocation
 */
final class NullVendorPluginSettingsProvider implements VendorPluginSettingsProvider
{
    public function seedSettings(): void
    {
    }

    public function settingKeys(): array
    {
        return [];
    }
}
