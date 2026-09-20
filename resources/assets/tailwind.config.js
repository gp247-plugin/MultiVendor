/*
 * Tailwind config for the MultiVendor plugin — SELF-CONTAINED to the plugin.
 *
 * WHY a plugin-local bundle: gp247/core compiles its admin shell CSS from its
 * OWN Blade only (vendor/gp247/core/.../tailwind.config.js does not scan
 * plugins), so any class this plugin uses that core never uses would be purged
 * and silently render unstyled. Building here keeps the plugin's markup free of
 * that constraint.
 *
 * preflight is OFF: this bundle is always loaded ALONGSIDE core's
 * `GP247/Core/AdminShell/css/admin.css`, which already ships Tailwind's base
 * reset. Emitting it twice would fight over base element styles.
 *
 * Content globs are relative to the CWD: run the build from the host Laravel
 * project root (see resources/assets/README.md).
 */

/** @type {import('tailwindcss').Config} */
module.exports = {
    // Dark mode is toggled by the `.dark` class core's admin.js manages.
    darkMode: 'class',
    corePlugins: {
        preflight: false,
    },
    content: [
        'app/GP247/Plugins/MultiVendor/Views/**/*.blade.php',
        'app/GP247/Plugins/MultiVendor/template/**/*.blade.php',
    ],
    safelist: [
        {
            // WHY: order/payment status colours come from the DB (status id ->
            // colour map in the Blade's @php block), so they never appear
            // literally in scanned source.
            pattern: /^(bg|text|border)-(gray|slate|red|orange|amber|yellow|green|emerald|teal|sky|blue|indigo|purple|pink)-(50|100|200|300|500|600|700|800|900)$/,
            variants: ['dark', 'hover'],
        },
    ],
    theme: {
        extend: {},
    },
    plugins: [],
};
