> 🌐 **Language:** [🇻🇳 Tiếng Việt](./how_to_setup_vi.md) · 🇬🇧 English (current)

# MultiVendor installation guide

## Introduction
This guide walks through installing S-Cart 3.x, installing and enabling the MultiVendor plugin, creating the first vendor, and verifying that the marketplace works. It is for site owners or basic technical administrators; follow the steps as written and it runs, no programming needed. How the marketplace operates and its settings are in the [Detailed guide](./multi-vendor-detail_en.md).

## Requirements
- PHP **8.3** or newer, Composer, MySQL/MariaDB (SQLite only for quick trials).
- S-Cart **3.x** with `gp247/core` **3.0** and `gp247/shop` installed (the plugin requires `gp247/shop`).
- The MultiStore plugin is **not** installed on the same site (the two are mutually exclusive).
- Permission to run `php artisan` on the server (or use the admin-UI install in Step 2).

## Step 1: Install S-Cart 3.x
Skip this step if your S-Cart 3.x site is already running.

1. Open a **Terminal** in the folder where the site should live and run:

   ```bash
   composer create-project gp247/s-cart website-folder
   cd website-folder
   ```

   On success the `website-folder` directory appears with a ready `.env` file.

2. Open `.env` and set the database connection for your machine:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=s-cart
   DB_USERNAME=root
   DB_PASSWORD=
   ```

3. Generate the application key:

   ```bash
   php artisan key:generate
   ```

4. Initialise S-Cart (tables, default data, admin account):

   ```bash
   php artisan gp247:install
   ```

   Answer the prompts on screen. On success the command prints the admin URL and login.

5. (Optional) Load shop sample data so there are products to test with:

   ```bash
   php artisan gp247:shop-sample
   ```

See also: [S-Cart overview](https://gp247.net/en/s-cart/s-cart-overview.html).

## Step 2: Install the MultiVendor plugin
Pick **one** of the three methods.

**Method 1 — From the GP247 library (recommended)**
1. Sign in to the admin area → **Extensions** → **Plugins**.
2. Open the **Library** tab, find **MultiVendor**, click **Install**.

**Method 2 — Import a zip file**
1. Go to **Extensions** → **Plugins** → **Import file** tab.
2. Choose the plugin zip and upload it.

**Method 3 — Manual copy (when the two above fail)**
1. Extract and copy the source folder to `app/GP247/Plugins/MultiVendor/`.
2. Copy the plugin's `public` folder to `public/GP247/Plugins/MultiVendor/`.
3. Go to **Extensions** → **Plugins** → **Local storage** tab, find **MultiVendor**, click **Install**.

On success the plugin appears in the list with an **Enable** button. Details: [Installing extensions](https://gp247.net/en/user-guide-extension/guide-to-installing-the-extension.html).

## Step 3: Enable and configure the marketplace
1. In the plugin list, click **Enable** on the MultiVendor row. The **Marketplace** menu appears in the admin sidebar.
2. Go to **Marketplace** → **Quick Configuration** and set:
   - **Commission rate (%)** kept by the marketplace.
   - **Allow vendor registration** (on if vendors may self-register).
   - **Auto approve vendor** / **Auto approve product** (off for manual moderation).
   - **Quick order** (Pro — shown locked on Free; on for dealer/B2B marketplaces).
3. Click **Save**. Each setting is explained in the [Detailed guide](./multi-vendor-detail_en.md#marketplace-settings).

## Step 4: Create the first vendor
**Option A — Admin creates it**
1. **Marketplace** → **Vendor store** → **Add new**: enter a store code (letters/digits only, at most 20 characters, e.g. `vendor01`), name, description → **Save**.
2. **Marketplace** → **Vendor user** → **Add new**: enter email, password, pick the store just created, status **Active** → **Save**.
3. Open `https://your-domain/vendor_admin` and sign in with that email/password. On success you see the store dashboard.

**Option B — Vendor self-registers** (when "Allow vendor registration" is on)
1. The vendor opens `https://your-domain/vendor_admin/register`, fills in the details and a store code.
2. If "Auto approve vendor" is off, the store is pending; the admin opens **Vendor store** and switches its status on so the vendor can sign in.

**Sample data for a quick trial** (test sites only):

```bash
php artisan gp247:vendor-sample
```

The command creates 3 sample stores `vendor01`–`vendor03`. Each one gets a vendor login, a
supplier, 3 categories of its own and 9 products, 3 in each category, so a store page and its
category filter both have something to show:

| Store page | Vendor login | Password |
| --- | --- | --- |
| `/vendor/vendor01` | `vendor01@gp247.local` | `123456` |
| `/vendor/vendor02` | `vendor02@gp247.local` | `123456` |
| `/vendor/vendor03` | `vendor03@gp247.local` | `123456` |

Three stores is the Free edition's vendor limit, so the sample fits either edition. Running the
command again replaces its own sample stores instead of adding more, and it never touches a store
you created yourself. Change or delete these accounts before a site goes live.

## Step 5: Verify
1. Open `https://your-domain/vendor/vendor01` — the store page shows the vendor's categories and products (empty until products are posted).
2. Sign in to `/vendor_admin`, create a product; if auto-approve is off, approve it in the S-Cart admin → Products.
3. Buy products from two different vendors in one cart and check out — you must get **two orders**, each belonging to one store.
4. Check system health:

   ```bash
   php artisan gp247:info
   php artisan gp247:doctor
   ```

## Custom paths (optional)
Add to `.env` to change the default paths, then run `php artisan gp247:cache-rebuild`:

```env
MULTIVENDOR_FRONT_PATH=vendor
MULTIVENDOR_ADMIN_PATH=vendor_admin
PREFIX_QUICK_ORDER_VENDOR=quick-order
PREFIX_CATEGORY_VENDOR=category-vendor
```

| Variable | Which path it changes | Default |
| --- | --- | --- |
| `MULTIVENDOR_FRONT_PATH` | The store directory and the store pages customers see: `/vendor`, `/vendor/{code}` | `vendor` |
| `MULTIVENDOR_ADMIN_PATH` | The vendor's own admin area: `/vendor_admin`, including its login and register pages | `vendor_admin` |
| `PREFIX_QUICK_ORDER_VENDOR` | The last segment of the quick-order page: `/vendor/{code}/quick-order` | `quick-order` |
| `PREFIX_CATEGORY_VENDOR` | The store's own category pages | `category-vendor` |

Three things to know before you change them:

- **Do not collide** with a path that already exists: the admin prefix (`gp247_admin` by default), the other variables in this table, or a storefront page/product slug. A collision leaves one of the two pages unreachable.
- **The old paths start returning 404.** On a live site that search engines have already indexed, set up 301 redirects from the old paths to the new ones before you switch.
- Write the value **without a leading or trailing `/`**. On hosting without a command line, delete `bootstrap/cache/config.php` (if it exists) after editing `.env` instead of running `gp247:cache-rebuild`.

To change wording, e-mail content, the store page layout or staff permissions, see the **Customisation** section of the [Detailed guide](./multi-vendor-detail_en.md#customisation).

## Conditions & Rules (know before you act)
- **The plugin refuses to install if MultiStore is installed** — both use the shared store mechanism under different models; remove MultiStore first.
- **`gp247/shop` must be installed** — the plugin relies on shop products, cart and orders.
- **The store code is unique, at most 20 characters** — it becomes the path `/vendor/{code}`.
- **Uninstalling removes vendor accounts, store categories and the vendor payout ledger** (S-Cart products and orders are kept) — export what you need before uninstalling.

## Troubleshooting
| Symptom | Fix |
| --- | --- |
| No **Marketplace** menu | Check the plugin is **Enabled** (not just Installed); run `php artisan gp247:cache-rebuild` |
| `/vendor_admin` says "account inactive" | The admin must switch on the vendor account status and the store status |
| Write permission errors | Grant write permission on `storage/` and `bootstrap/cache/` |
| Database connection error | Re-check the `DB_*` lines in `.env` |
| Need the detailed error | Open `storage/logs/laravel.log` |

## Q&A
**Q1: My site runs S-Cart 2.x or still uses the old `sc:*` commands — can I install?**

→ The plugin requires `gp247/core` 3.0. Upgrade S-Cart to 3.x first; the `gp247:*` commands replace the old `sc:*` ones.

**Q2: I cannot run commands on my hosting — what then?**

→ Use Method 1 or 2 in Step 2 (through the admin UI). The plugin needs no cron or queue to run.

**Q3: Where do vendors sign in, and where does the marketplace admin sign in?**

→ Vendors: `/vendor_admin`. Marketplace admin: the regular S-Cart admin area.

**Q4: Can I rename the `/vendor` path?**

→ Yes, set `MULTIVENDOR_FRONT_PATH` in `.env` and run `php artisan gp247:cache-rebuild`.

**Q5: Can the sample data be removed?**

→ Delete stores `vendor01`–`vendor03` and their accounts manually under **Marketplace**. Never run the sample command on a live site.

---

<sub>📅 **Last updated:** 2026-09-21 · ✍️ **Author:** GP247</sub>
