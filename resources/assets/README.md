# MultiVendor — asset build (plugin maintainers only)

These are the **source** assets for the plugin's admin UI. They compile into
`../../public/css/admin.css`, which is **committed and ships with the plugin**;
on install the extension installer copies the plugin's `public/` folder to
`public/GP247/Plugins/MultiVendor/`, and the views load it via
`gp247_file('GP247/Plugins/MultiVendor/css/admin.css')`.

A consuming Laravel app never builds anything.

## Why a plugin-local Tailwind bundle

`gp247/core` compiles its admin shell CSS from its **own** Blade only — its
`tailwind.config.js` deliberately does not scan plugins, to keep core standalone.
Any Tailwind class this plugin uses that core happens not to use would therefore
be missing from `GP247/Core/AdminShell/css/admin.css` and render unstyled. This
bundle removes that constraint.

It is built with `preflight` **off** and without `@tailwind base`, because it is
always loaded alongside core's `admin.css`, which already ships the base reset.

## Rebuilding (after changing any Blade in this plugin)

Requires Node + a dev `tailwindcss` **v3**. Run from the **host Laravel project
root** (content globs are relative to the CWD):

```bash
npx tailwindcss@3 \
  -c app/GP247/Plugins/MultiVendor/resources/assets/tailwind.config.js \
  -i app/GP247/Plugins/MultiVendor/resources/assets/css/admin.css \
  -o app/GP247/Plugins/MultiVendor/public/css/admin.css --minify
```

Then copy the result to the running site's public folder (or re-run the
extension install/publish step):

```bash
cp -r app/GP247/Plugins/MultiVendor/public/. public/GP247/Plugins/MultiVendor/
```

Commit `public/css/admin.css` to the plugin repo.
