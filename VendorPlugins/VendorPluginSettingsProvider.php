<?php

namespace App\GP247\Plugins\MultiVendor\VendorPlugins;

/**
 * What the marketplace settings screen needs from the "vendors may configure
 * plugins" feature without owning it.
 *
 * The feature (which installed plugins a vendor may configure for its own
 * store, and the vendor screens that do it) is paid. The free settings screen
 * only needs the list of per-plugin on/off keys to show alongside its own, and
 * a way to make sure those rows exist. The paid package registers an
 * implementation under `config('Plugins/MultiVendor.vendor_plugins.provider')`;
 * without one, NullVendorPluginSettingsProvider has no keys to show.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-paid-code-location
 * @aidlc-adr multi-vendor_pro-code-relocation
 */
interface VendorPluginSettingsProvider
{
    /** Make sure one on/off row exists per eligible plugin (idempotent). */
    public function seedSettings(): void;

    /**
     * Config keys of the per-plugin flags, for the marketplace settings screen.
     *
     * @return array<int, string>
     */
    public function settingKeys(): array;
}
