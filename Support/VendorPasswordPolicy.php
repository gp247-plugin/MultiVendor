<?php

namespace App\GP247\Plugins\MultiVendor\Support;

use GP247\Core\Support\Password\PasswordAudience;
use GP247\Core\Support\Password\PasswordPolicy;

/**
 * The vendor account group of the core Password policy screen: its defaults, the
 * config rows install and convergence write, its label rows, and the rules every
 * vendor password path uses — one source for the provider, AppConfig and the screens.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-vendor-password-policy
 * @aidlc-adr admin-shell_password-policy-registry
 */
final class VendorPasswordPolicy
{
    public const AUDIENCE = 'vendor';
    /** Fresh install: vendors sign in to an admin area with orders and payouts, so the admin default applies. */
    public const DEFAULT_MIN = 12;
    public const DEFAULT_MAX = 64;
    /** Sites that already run the plugin keep what it enforced: 6 minimum and no maximum (72 = what bcrypt hashes). */
    public const LEGACY_MIN = 6;
    public const LEGACY_MAX = 72;
    public const LABEL = 'admin.password_policy.audience_vendor';

    /**
     * The group the plugin registers with core.
     *
     * @return PasswordAudience
     */
    public static function audience(): PasswordAudience
    {
        return new PasswordAudience(self::AUDIENCE, self::LABEL, self::DEFAULT_MIN, self::DEFAULT_MAX);
    }

    /**
     * Rules of a vendor password field (the policy of the Vendors block).
     *
     * @param bool $required True on sign-up / account creation; false where blank keeps the current one.
     * @return array<int, mixed>
     */
    public static function rules(bool $required): array
    {
        return [$required ? 'required' : 'nullable', 'string', gp247_password_rule(self::AUDIENCE)];
    }

    /**
     * Site-wide (GLOBAL) config rows of the vendor policy, every character switch off.
     *
     * @param int $min
     * @param int $max
     * @return list<array<string, mixed>>
     */
    public static function configRows(int $min, int $max): array
    {
        $values = ['min' => (string) $min, 'max' => (string) $max, 'letter' => '0', 'mixedcase' => '0', 'number' => '0', 'symbol' => '0'];
        $details = ['min' => 'min', 'max' => 'max', 'letter' => 'letter', 'mixedcase' => 'mixed', 'number' => 'number', 'symbol' => 'symbol'];
        $rows = [];
        foreach (PasswordPolicy::FIELDS as $sort => $field) {
            $rows[] = [
                'group'    => 'global',
                'code'     => 'password_policy',
                'key'      => PasswordPolicy::key(self::AUDIENCE, $field),
                'value'    => $values[$field],
                'sort'     => $sort,
                'detail'   => 'admin.password_policy.' . $details[$field],
                'store_id' => (string) GP247_STORE_ID_GLOBAL,
            ];
        }

        return $rows;
    }

    /**
     * Label rows (en / vi) of the Vendors block; Japanese comes from Locales/ja.php.
     * Position `multi_vendor`, so an uninstall removes them with the plugin's other text.
     *
     * @return list<array{code: string, text: string, position: string, location: string}>
     */
    public static function languageRows(): array
    {
        return [
            ['code' => self::LABEL, 'text' => 'Vendors', 'position' => 'multi_vendor', 'location' => 'en'],
            ['code' => self::LABEL, 'text' => 'Người bán (gian hàng)', 'position' => 'multi_vendor', 'location' => 'vi'],
        ];
    }
}
