<?php

namespace App\GP247\Plugins\MultiVendor\VendorPlugins;

use App\GP247\Plugins\MultiVendor\Tier\Tier;

/**
 * The marketplace settings screen's one door to the paid "vendors may configure
 * plugins" feature: which per-plugin on/off keys to show, and seeding them.
 * Forwards to the VendorPluginSettingsProvider a paid plugin registered under
 * `config('Plugins/MultiVendor.vendor_plugins.provider')`; with none, or an
 * edition without the feature, there are simply no such keys.
 *
 * Resolved on every call on purpose (a `new` of a stateless class): caching
 * would hide the paid plugin being enabled or disabled inside one process.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-paid-code-location
 * @aidlc-adr multi-vendor_pro-code-relocation
 */
final class VendorPlugins
{
    /** Config path a paid plugin writes its VendorPluginSettingsProvider class name to. */
    public const PROVIDER_CONFIG = 'Plugins/MultiVendor.vendor_plugins.provider';

    /**
     * The registered provider, or the null provider when there is none or the
     * edition does not include the feature.
     */
    public static function provider(): VendorPluginSettingsProvider
    {
        if (!Tier::allows(Tier::F_VENDOR_PLUGINS)) {
            return new NullVendorPluginSettingsProvider();
        }
        $class = config(self::PROVIDER_CONFIG);
        if (is_string($class) && $class !== '' && class_exists($class)) {
            $provider = new $class();
            if ($provider instanceof VendorPluginSettingsProvider) {
                return $provider;
            }
        }

        return new NullVendorPluginSettingsProvider();
    }

    /** @see VendorPluginSettingsProvider::seedSettings() */
    public static function seedSettings(): void
    {
        self::provider()->seedSettings();
    }

    /**
     * @see VendorPluginSettingsProvider::settingKeys()
     * @return array<int, string>
     */
    public static function settingKeys(): array
    {
        return self::provider()->settingKeys();
    }
}
