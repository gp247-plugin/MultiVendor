<?php

namespace App\GP247\Plugins\MultiVendor\Commands;

use App\GP247\Plugins\MultiVendor\Models\VendorCategory;
use App\GP247\Plugins\MultiVendor\Models\VendorCategoryDescription;
use App\GP247\Plugins\MultiVendor\Models\VendorProductCategory;
use App\GP247\Plugins\MultiVendor\Models\VendorUser;
use Carbon\Carbon;
use GP247\Core\Library\ExtensionInstaller;
use GP247\Core\Models\AdminConfig;
use GP247\Core\Models\AdminStore;
use GP247\Core\Models\AdminStoreDescription;
use GP247\Front\Models\FrontBanner;
use GP247\Front\Models\FrontLayoutBlock;
use GP247\Front\Models\FrontLink;
use GP247\Shop\Models\ShopCategory;
use GP247\Shop\Models\ShopProduct;
use GP247\Shop\Models\ShopProductCategory;
use GP247\Shop\Models\ShopProductDescription;
use GP247\Shop\Models\ShopSupplier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sample marketplace data for a demo or test site.
 *
 * Creates three vendor stores; each gets a login, a supplier, three categories
 * and nine products (three per category) - enough to open a store page, filter
 * it by category, sign in as a vendor and buy from two vendors in one cart.
 *
 * Three stores, not more: that is the Free edition's vendor quota
 * (Tier::FREE_VENDOR_QUOTA), and a command that seeds past the quota would put
 * a Free site in a state its own "add vendor" screen refuses to create.
 *
 * The command is re-entrant: it removes its own stores and everything hanging
 * off them before writing, so a second run replaces the sample rather than
 * duplicating it. It only ever touches the ST-VENDOR-* stores.
 *
 * Written against gp247 3.x: an entity belongs to a store through its own
 * `store_id` column (the 2.x `*_store` pivot models are gone), the store's
 * display name lives in admin_store_description.`name` (there is no `title`
 * column), and a product's physical/virtual flag is `product_type`, not `tag`.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-sales-docs-sync
 */
class SampleData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gp247:vendor-sample
        {--skip-rating : Do not touch the ProductRating plugin (offline site, or you manage plugins yourself)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed sample marketplace data: 3 vendor stores, each with a login, a supplier, 3 categories and 9 products';

    /**
     * The sample stores: store id => [code, English name, Vietnamese name].
     *
     * Stores stay open (status/active = 1) so every one of them can be visited
     * and signed into straight after seeding.
     */
    private const STORES = [
        'ST-VENDOR-01' => ['code' => 'vendor01', 'en' => 'Vendor 01', 'vi' => 'Nhà bán lẻ 01'],
        'ST-VENDOR-02' => ['code' => 'vendor02', 'en' => 'Vendor 02', 'vi' => 'Nhà bán lẻ 02'],
        'ST-VENDOR-03' => ['code' => 'vendor03', 'en' => 'Vendor 03', 'vi' => 'Nhà bán lẻ 03'],
    ];

    /**
     * The review plugin a demo marketplace needs: the shop page's Reviews tab is
     * rendered by ProductRating (ADR-multi-vendor-storefront-shop-page), so
     * without it a seeded demo is missing exactly the trust signals it should be
     * showing off.
     */
    private const RATING_PLUGIN = 'ProductRating';

    /** Password of every sample vendor login. Test sites only. */
    private const SAMPLE_PASSWORD = '123456';

    /** Products seeded in each category; three categories per store make nine. */
    private const PRODUCTS_PER_CATEGORY = 3;

    /**
     * The three categories of each store, in both languages. The category name
     * also names the products inside it, so a store page reads like a catalogue
     * rather than nine copies of one line.
     */
    private const CATEGORIES = [
        'vendor01' => [
            ['en' => 'Electronics', 'vi' => 'Điện tử'],
            ['en' => 'Phones & Tablets', 'vi' => 'Điện thoại & Máy tính bảng'],
            ['en' => 'Accessories', 'vi' => 'Phụ kiện'],
        ],
        'vendor02' => [
            ['en' => 'Clothing & Fashion', 'vi' => 'Thời trang & Quần áo'],
            ['en' => 'Shoes', 'vi' => 'Giày dép'],
            ['en' => 'Bags', 'vi' => 'Túi xách'],
        ],
        'vendor03' => [
            ['en' => 'Home & Garden', 'vi' => 'Nhà cửa & Vườn'],
            ['en' => 'Kitchen', 'vi' => 'Nhà bếp'],
            ['en' => 'Furniture', 'vi' => 'Nội thất'],
        ],
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if (($missing = $this->missingTable()) !== null) {
            $this->error('The MultiVendor plugin is enabled but its table "'.$missing.'" is missing — the install did not finish.');
            $this->line('Reinstall the plugin, then run this command again.');

            return self::FAILURE;
        }

        // WHY before the transaction and not inside it: installing a plugin runs
        // Schema DDL, MySQL auto-commits DDL, and that would close the seed
        // transaction half way through — which this one would then re-run, since
        // it retries twice. RISK-TECH-mv-sample-ddl-in-transaction.
        $this->ensureRatingPlugin();

        try {
            DB::connection(GP247_DB_CONNECTION)->transaction(function () {
                $this->cleanData();
                $this->createStores();
                $this->createSuppliers();
                $this->createVendorUsers();
                $this->createVendorCategories();
                $this->createProducts();
                $this->renameRootStore();

                foreach (array_keys(self::STORES) as $storeId) {
                    $store = AdminStore::where('id', $storeId)->first();
                    if ($store) {
                        AdminStore::setUpDataDefault($store);
                    }
                }
            }, 2);
        } catch (\Throwable $e) {
            $this->error('Error generating sample data: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Store', 'Store page', 'Vendor login', 'Password'],
            array_map(
                fn (array $store) => [
                    $store['en'],
                    // WHY the route and not a literal: the marketplace prefix is
                    // configurable (MULTIVENDOR_FRONT_PATH), so a hard-coded
                    // '/vendor/…' printed a path that no longer exists.
                    gp247_route_front('MultiVendor.detail', ['code' => $store['code']]),
                    $this->loginOf($store['code']),
                    self::SAMPLE_PASSWORD,
                ],
                array_values(self::STORES)
            )
        );
        $this->info('Sample data generation completed successfully!');

        return self::SUCCESS;
    }

    /**
     * Settle the review plugin so the seeded demo has a Reviews tab.
     *
     * Delegates the install itself to gp247:ext-install, which already tells the
     * three states apart that matter here: files on disk but not installed (it
     * activates them locally) versus not on disk at all (it resolves the item in
     * the marketplace and downloads it). Re-implementing that here would also
     * re-implement its marketplace resolution and its "API failed" versus "not
     * found" distinction — both of which have cost this project a named risk.
     *
     * Never fails the command: a marketplace this site cannot reach, or a paid
     * item without a license, costs the demo its Reviews tab, not its data.
     *
     * @return void
     *
     * @aidlc-unit multi-vendor-pro
     * @aidlc-story US-multi-vendor-pro-sales-docs-sync
     */
    protected function ensureRatingPlugin(): void
    {
        // WHY skip under PHPUnit: the tests that exercise this command run inside
        // DatabaseTransactions, and an install's DDL would auto-commit and tear
        // that wrapper down. The decision itself is unit-tested through
        // ratingPluginAction(); doing it for real belongs to a real site.
        if (app()->runningUnitTests()) {
            return;
        }

        switch ($this->ratingPluginAction((bool) $this->option('skip-rating'))) {
            case 'skipped':
                $this->line('Review plugin: skipped (--skip-rating).');

                return;

            case 'already_active':
                $this->info('Review plugin: already installed and on.');

                return;

            case 'enable':
                $response = (new ExtensionInstaller)->enable('Plugins', self::RATING_PLUGIN);
                if (is_array($response) && ($response['error'] ?? 1) == 0) {
                    $this->info('Review plugin: was installed but off — switched on.');

                    return;
                }
                $this->warnRatingPlugin('could not switch it on: '.(is_array($response) ? ($response['msg'] ?? '') : ''));

                return;

            default:
                $exit = Artisan::call('gp247:ext-install', ['--type' => 'plugin', '--key' => [self::RATING_PLUGIN]]);
                if ($exit !== 0) {
                    $this->warnRatingPlugin(trim(Artisan::output()));

                    return;
                }
                // A fresh install leaves the plugin installed; switch it on too,
                // otherwise the Reviews tab still will not render.
                if (!gp247_extension_check_active('Plugins', self::RATING_PLUGIN)) {
                    (new ExtensionInstaller)->enable('Plugins', self::RATING_PLUGIN);
                }
                $this->info('Review plugin: installed and switched on.');
        }
    }

    /**
     * What this command would do about the review plugin, given the state of the
     * site. Pure: reads the plugin's state, changes nothing.
     *
     * @param bool $skip Whether the operator asked to leave the plugin alone.
     * @return string One of skipped|already_active|enable|install.
     *
     * @aidlc-unit multi-vendor-pro
     * @aidlc-story US-multi-vendor-pro-sales-docs-sync
     */
    protected function ratingPluginAction(bool $skip = false): string
    {
        if ($skip) {
            return 'skipped';
        }
        if (!gp247_extension_check_installed('Plugins', self::RATING_PLUGIN)) {
            // On disk or marketplace-only is gp247:ext-install's distinction.
            return 'install';
        }

        return gp247_extension_check_active('Plugins', self::RATING_PLUGIN) ? 'already_active' : 'enable';
    }

    /**
     * Report a review-plugin problem without failing the seed, and hand over the
     * exact command that finishes the job.
     *
     * @param string $reason What went wrong.
     * @return void
     */
    protected function warnRatingPlugin(string $reason): void
    {
        $this->warn('Review plugin ('.self::RATING_PLUGIN.') not set up — the shop pages will have no Reviews tab.');
        if ($reason !== '') {
            $this->line('  Reason: '.$reason);
        }
        $this->line('  Free:  php artisan gp247:ext-install --type=plugin --key='.self::RATING_PLUGIN);
        $this->line('  Paid:  php artisan gp247:ext-install --type=plugin --key='.self::RATING_PLUGIN.' --paid --license=<license>');
        $this->line('  If the marketplace refused this domain: php artisan gp247:ext-register-license');
        $this->line('  Sample data itself was seeded; re-run this command after installing, or use --skip-rating.');
    }

    /**
     * The first plugin table that is missing, or null when the plugin's install
     * did reach the database.
     *
     * WHY the models' own names: the plugin creates its tables through its
     * models (VendorUser::install), which do not carry the core table prefix —
     * `vendor_user`, while a core table is `gp247_admin_store`.
     *
     * @return string|null
     */
    private function missingTable(): ?string
    {
        foreach ([new VendorUser, new VendorCategory] as $model) {
            $schema = Schema::connection($model->getConnectionName());
            if (!$schema->hasTable($model->getTable())) {
                return $model->getTable();
            }
        }

        return null;
    }

    /**
     * Remove a previous run: the sample stores and everything that belongs to
     * them. In 3.x an entity carries its own `store_id`, so every table below
     * is cleaned by that column instead of by a pivot table.
     *
     * @return void
     */
    private function cleanData(): void
    {
        $storeIds = array_keys(self::STORES);

        $productIds = ShopProduct::whereIn('store_id', $storeIds)->pluck('id')->toArray();
        if ($productIds) {
            ShopProductDescription::whereIn('product_id', $productIds)->delete();
            ShopProductCategory::whereIn('product_id', $productIds)->delete();
            VendorProductCategory::whereIn('product_id', $productIds)->delete();
            ShopProduct::whereIn('id', $productIds)->delete();
        }

        $vendorCategoryIds = VendorCategory::whereIn('store_id', $storeIds)->pluck('id')->toArray();
        if ($vendorCategoryIds) {
            VendorCategoryDescription::whereIn('vendor_category_id', $vendorCategoryIds)->delete();
            VendorCategory::whereIn('store_id', $storeIds)->delete();
        }

        ShopSupplier::whereIn('store_id', $storeIds)->delete();
        VendorUser::whereIn('store_id', $storeIds)->delete();
        FrontBanner::whereIn('store_id', $storeIds)->delete();
        FrontLink::whereIn('store_id', $storeIds)->delete();
        FrontLayoutBlock::whereIn('store_id', $storeIds)->delete();
        AdminConfig::whereIn('store_id', $storeIds)->delete();
        AdminStoreDescription::whereIn('store_id', $storeIds)->delete();
        AdminStore::whereIn('id', $storeIds)->delete();
    }

    /**
     * Create the three vendor stores and their per-language descriptions.
     *
     * Mirrors the "Add new vendor" screen (VendorStoreCreateForm): no domain,
     * and language/currency left empty because a booth inherits both from the
     * marketplace — this is a single-domain marketplace.
     *
     * @return void
     */
    private function createStores(): void
    {
        $rows = [];
        $descriptions = [];
        foreach (self::STORES as $storeId => $store) {
            $rows[] = [
                'id' => $storeId,
                'code' => $store['code'],
                'email' => $store['code'].'@gp247.local',
                'address' => 'Address of '.$store['en'],
                'phone' => '0909090909',
                'template' => $this->marketplaceTemplate(),
                'domain' => '',
                'language' => '',
                'currency' => '',
                'status' => 1,
                'active' => 1,
                // The plugin ships vendor1.png..vendor4.png, so the zero of the
                // store code (vendor01) is dropped when naming the file.
                'logo' => '/GP247/Plugins/MultiVendor/images/vendor'.(int) substr($store['code'], -2).'.png',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];

            foreach (['en' => $store['en'], 'vi' => $store['vi']] as $lang => $name) {
                $descriptions[] = [
                    'store_id' => $storeId,
                    'lang' => $lang,
                    // WHY `name`: admin_store_description has no `title` column in
                    // 3.x — AdminStore::getTitle() reads `name`.
                    'name' => $name,
                    'description' => '',
                    'keyword' => '',
                    'maintain_content' => '<center><img src="/GP247/Core/images/maintenance.jpg" /></center>',
                    'maintain_note' => 'Website is in maintenance mode!',
                ];
            }
        }

        AdminStore::insert($rows);
        AdminStore::insertDescription($descriptions);
    }

    /**
     * One login per store, so every sample store can actually be signed into.
     *
     * @return void
     */
    private function createVendorUsers(): void
    {
        foreach (self::STORES as $storeId => $store) {
            VendorUser::create([
                'id' => 'VUS-'.strtoupper($store['code']),
                'store_id' => $storeId,
                'first_name' => $store['en'],
                'last_name' => 'Owner',
                'email' => $this->loginOf($store['code']),
                'password' => bcrypt(self::SAMPLE_PASSWORD),
                'country' => 'VN',
                'status' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * One supplier per store.
     *
     * @return void
     */
    private function createSuppliers(): void
    {
        foreach (self::STORES as $storeId => $store) {
            ShopSupplier::create([
                'id' => 'SUP-'.strtoupper($store['code']),
                'name' => 'Supplier of '.$store['en'],
                'alias' => 'supplier-'.$store['code'],
                'email' => 'supplier-'.$store['code'].'@gp247.local',
                'phone' => '0909090909',
                'address' => 'Address of '.$store['en'],
                'status' => 1,
                'store_id' => $storeId,
                'sort' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Three categories per store, in both languages.
     *
     * @return void
     */
    private function createVendorCategories(): void
    {
        $categories = [];
        $descriptions = [];
        foreach (self::STORES as $storeId => $store) {
            foreach (self::CATEGORIES[$store['code']] as $index => $titles) {
                $categoryId = $this->vendorCategoryId($store['code'], $index + 1);
                $categories[] = [
                    'id' => $categoryId,
                    'store_id' => $storeId,
                    'alias' => 'category-'.$store['code'].'-'.($index + 1),
                    'status' => 1,
                    'sort' => $index,
                    'image' => $this->sampleImage($store['code'].'-'.($index + 1), 'category'),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];

                foreach ($titles as $lang => $title) {
                    $descriptions[] = [
                        'vendor_category_id' => $categoryId,
                        'lang' => $lang,
                        'title' => $title,
                        'keyword' => '',
                        'description' => $title,
                    ];
                }
            }
        }

        VendorCategory::insert($categories);
        VendorCategoryDescription::insert($descriptions);
    }

    /**
     * Nine products per store, three in each of its categories.
     *
     * Enough for a store page to show more than one row, for the category
     * filter to actually filter, and for a cart to hold products of two
     * different vendors.
     *
     * WHY the `store_id` column: 3.x dropped the shop_product_store pivot. The
     * column is NOT NULL and defaults to the root store, so a product seeded
     * without it silently belongs to the marketplace instead of the vendor and
     * the vendor's store page stays empty.
     *
     * @return void
     */
    private function createProducts(): void
    {
        // Shop categories are optional here: a site that has not seeded the shop
        // sample data has none, and that must not stop the marketplace sample.
        // Where they exist the products are spread over them instead of piling
        // into one, so browsing the marketplace finds them on more than one page.
        $shopCategoryIds = ShopCategory::where('status', 1)->orderBy('id')->limit(9)->pluck('id')->all();

        $price = $this->samplePrice();
        $descriptions = [];
        $vendorLinks = [];
        $shopLinks = [];

        foreach (self::STORES as $storeId => $store) {
            $number = 0;
            foreach (self::CATEGORIES[$store['code']] as $index => $titles) {
                $categoryId = $this->vendorCategoryId($store['code'], $index + 1);

                for ($inCategory = 1; $inCategory <= self::PRODUCTS_PER_CATEGORY; $inCategory++) {
                    $number++;
                    $productId = $this->productId($store['code'], $number);
                    $suffix = $store['code'].'-'.$number;

                    ShopProduct::create([
                        'id' => $productId,
                        'sku' => 'SKU-'.strtoupper($suffix),
                        'alias' => 'sample-product-'.$suffix,
                        'image' => $this->sampleImage($suffix),
                        'supplier_id' => 'SUP-'.strtoupper($store['code']),
                        'store_id' => $storeId,
                        // A spread of prices rather than nine identical ones, so
                        // sorting and filtering by price have something to do.
                        'price' => $price * $number,
                        'cost' => 0,
                        'stock' => 100,
                        'sold' => 0,
                        'minimum' => 1,
                        'weight_class' => 'kg',
                        'weight' => 1,
                        'length_class' => 'cm',
                        'length' => 10,
                        'width' => 10,
                        'height' => 10,
                        'kind' => 0,
                        // WHY `product_type`: 3.x replaced the `tag` column with a
                        // physical/virtual string.
                        'product_type' => 'physical',
                        'tax_id' => 0,
                        'status' => 1,
                        'approve' => 1,
                        'sort' => $number,
                        'view' => 0,
                        'date_available' => Carbon::now()->format('Y-m-d H:i:s'),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);

                    $descriptions[] = [
                        'product_id' => $productId,
                        'lang' => 'en',
                        'name' => $titles['en'].' '.$inCategory.' of '.$store['en'],
                        'keyword' => 'sample, product',
                        'description' => 'Sample product sold by '.$store['en'].'.',
                        'content' => '<p>Sample product sold by '.$store['en'].'.</p>',
                    ];
                    $descriptions[] = [
                        'product_id' => $productId,
                        'lang' => 'vi',
                        'name' => $titles['vi'].' '.$inCategory.' của '.$store['vi'],
                        'keyword' => 'sample, product',
                        'description' => 'Sản phẩm mẫu bán bởi '.$store['vi'].'.',
                        'content' => '<p>Sản phẩm mẫu bán bởi '.$store['vi'].'.</p>',
                    ];

                    $vendorLinks[] = [
                        'product_id' => $productId,
                        'vendor_category_id' => $categoryId,
                    ];

                    if ($shopCategoryIds) {
                        $shopLinks[] = [
                            'product_id' => $productId,
                            'category_id' => $shopCategoryIds[($number - 1) % count($shopCategoryIds)],
                        ];
                    }
                }
            }
        }

        ShopProductDescription::insert($descriptions);
        VendorProductCategory::insert($vendorLinks);
        if ($shopLinks) {
            ShopProductCategory::insert($shopLinks);
        }
    }

    /**
     * Placeholder photo for a sample product.
     *
     * WHY a remote placeholder: this is the same source gp247:shop-sample uses
     * for its own demo products, so a marketplace demo looks like the shop demo
     * around it, and the plugin carries no stock photos of its own. It needs
     * internet; offline, the card falls back to the theme's "no image" box and
     * everything else still works.
     *
     * @param string $code
     * @param string $kind Distinguishes the product photo from the category one.
     * @return string
     */
    private function sampleImage(string $code, string $kind = 'product'): string
    {
        return 'https://picsum.photos/500/500?random='.$kind.'-'.(int) substr($code, -2);
    }

    /**
     * A sample price that reads sensibly in the marketplace's own currency.
     *
     * WHY it is not a fixed number: a currency with no decimals (VND) and one
     * with two (USD) are three orders of magnitude apart, so a single constant
     * shows up as either a rounding error or a small fortune.
     *
     * @return int
     */
    private function samplePrice(): int
    {
        $precision = (int) (gp247_currency_info()['precision'] ?? 2);

        return $precision === 0 ? 100000 : 100;
    }

    /**
     * Label the marketplace itself, so a demo site says what it is.
     *
     * @return void
     */
    private function renameRootStore(): void
    {
        AdminStoreDescription::where('store_id', (string) GP247_STORE_ID_ROOT)
            ->whereIn('lang', ['en', 'vi'])
            // WHY `name`, not `title`: see createStores().
            ->update(['name' => 'GP247 demo marketplace']);
    }

    /**
     * The storefront template the marketplace runs, so a booth renders with the
     * same theme as the site around it.
     *
     * @return string
     */
    private function marketplaceTemplate(): string
    {
        return (string) (AdminStore::where('id', (string) GP247_STORE_ID_ROOT)->value('template') ?: '');
    }

    /**
     * Login name of a store's sample vendor.
     *
     * @param string $code
     * @return string
     */
    private function loginOf(string $code): string
    {
        return $code.'@gp247.local';
    }

    /**
     * Id of one of a store's sample vendor categories.
     *
     * @param string $code
     * @param int $number
     * @return string
     */
    private function vendorCategoryId(string $code, int $number): string
    {
        return 'VC-'.strtoupper($code).'-'.$number;
    }

    /**
     * Id of one of a store's sample products.
     *
     * @param string $code
     * @param int $number
     * @return string
     */
    private function productId(string $code, int $number): string
    {
        return 'PRD-'.strtoupper($code).'-'.$number;
    }
}
