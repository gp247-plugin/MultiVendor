<?php

namespace App\GP247\Plugins\MultiVendor\Storefront;

use App\GP247\Plugins\MultiVendor\Kyc\Kyc;
use GP247\Core\Models\AdminStore;
use GP247\Core\Models\AdminStoreDescription;
use GP247\Front\Models\FrontBanner;
use GP247\Shop\Models\ShopProduct;
use Illuminate\Support\Collection;

/**
 * Read model of a vendor's storefront ("shop page") and of the marketplace
 * directory: header metrics, the products tab (in-shop search / category /
 * sort), the store's banners, and the list of open stores.
 *
 * Ratings come from the ProductRating plugin's seller-level helpers when that
 * plugin is installed and enabled for the store; otherwise the page simply shows
 * no rating (S3 decision: reviews live in a shared plugin, MultiVendor displays
 * them per store).
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-storefront-shop-page
 * @aidlc-adr multi-vendor_storefront-shop-page
 */
final class ShopPageService
{
    public const TAB_PRODUCTS = 'products';
    public const TAB_REVIEWS = 'reviews';
    public const TAB_INFO = 'info';
    public const TABS = [self::TAB_PRODUCTS, self::TAB_REVIEWS, self::TAB_INFO];

    /** Storefront sort keys → [column, direction] (same set as the core product list). */
    public const SORTS = [
        'price_desc' => ['price', 'desc'],
        'price_asc' => ['price', 'asc'],
        'sort_desc' => ['sort', 'desc'],
        'sort_asc' => ['sort', 'asc'],
        'id_desc' => ['id', 'desc'],
        'id_asc' => ['id', 'asc'],
    ];

    /** Directory cards per page. */
    public const DIRECTORY_PER_PAGE = 12;

    /** Banners shown under the shop header. */
    public const BANNER_LIMIT = 6;

    /**
     * Banner type a shop page reads.
     *
     * A store carries banners for several places at once — the storefront home,
     * the breadcrumb strip, the marketplace's own blocks — and they are told
     * apart by this code. Without it the shop page showed whatever banners the
     * store happened to own, including ones meant for somewhere else entirely.
     *
     * WHY this code and not one of the plugin's own: gp247/front already ships
     * a "Banner store" type for exactly this surface, and it is seeded for every
     * store. A second, marketplace-only type would mean one more thing to create
     * at install, one more thing to explain, and a banner picker with two
     * near-identical entries.
     */
    public const VENDOR_BANNER_TYPE = 'banner-store';

    /**
     * Header metrics of a store.
     *
     * @return array{products: int, rating: array{total:int, average:float, distribution:array}|null, ratingEnabled: bool, joined: \Illuminate\Support\Carbon|null, verified: bool}
     */
    public static function stats(AdminStore $store): array
    {
        $rating = self::sellerRating((string) $store->id);

        return [
            'products' => self::productCounts([(string) $store->id])[(string) $store->id] ?? 0,
            'rating' => $rating,
            'ratingEnabled' => $rating !== null,
            'joined' => $store->created_at,
            'verified' => Kyc::isVerified((string) $store->id), // S3-2 badge (Pro)
            // S5-3: the shop's recent record, or null when there is too little of
            // it to publish. Free, like the page it sits on.
            'trust' => TrustSignals::for((string) $store->id),
        ];
    }

    /**
     * Seller rating summary, or null when ratings are unavailable for this store.
     *
     * WHY try/catch: the storefront must keep rendering when the ProductRating
     * plugin is mid-upgrade or its table is out of step (open-source, many
     * environments) — a broken review plugin must never 500 the shop page.
     *
     * @return array{total:int, average:float, distribution:array}|null
     */
    public static function sellerRating(string $storeId): ?array
    {
        if (!self::ratingEnabled($storeId)) {
            return null;
        }
        try {
            return gp247_product_rating_store_summary($storeId);
        } catch (\Throwable $e) {
            if (function_exists('gp247_report')) {
                gp247_report('MultiVendor shop page: ProductRating summary failed — '.$e->getMessage());
            }

            return null;
        }
    }

    /**
     * Whether the ProductRating plugin is present and switched on for this store.
     * Function-existence checks: the plugin may be absent, or not booted (tests).
     */
    public static function ratingEnabled(string $storeId): bool
    {
        return function_exists('gp247_product_rating_enabled')
            && function_exists('gp247_product_rating_store_summary')
            && gp247_product_rating_enabled($storeId);
    }

    /**
     * Sellable single products of the store, optionally narrowed by keyword
     * (core name/SKU search), vendor category and sort key. Paginated.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function products(string $storeId, string $keyword = '', ?string $categoryId = null, string $sort = '', ?int $perPage = null)
    {
        $query = (new ShopProduct)->start()
            ->setStore($storeId)
            ->setLimit($perPage ?: (int) (gp247_config('product_list') ?: 12))
            ->setPaginate()
            ->setSort(self::sortFor($sort));
        if (trim($keyword) !== '') {
            $query->setKeyword($keyword);
        }
        if ($categoryId !== null && $categoryId !== '') {
            $query->getProductToCategoryStore($categoryId);
        }

        return $query->getData();
    }

    /**
     * Map a storefront sort key to [column, direction]; unknown → default order.
     *
     * @return array{0: string, 1: string}
     */
    public static function sortFor(string $filterSort): array
    {
        return self::SORTS[$filterSort] ?? ['sort', 'asc'];
    }

    /**
     * Active banners the vendor uploaded for their shop page, in sort order.
     *
     * Only banners filed under VENDOR_BANNER_TYPE: everything else the store
     * owns belongs to another surface.
     *
     * @return Collection<int, FrontBanner>
     */
    public static function banners(string $storeId, int $limit = self::BANNER_LIMIT): Collection
    {
        return FrontBanner::where('store_id', $storeId)
            ->where('type', self::VENDOR_BANNER_TYPE)
            ->where('status', 1)
            ->orderBy('sort')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Open vendor stores (never the marketplace ROOT), optionally filtered by
     * name in the current locale. Paginated for the marketplace directory.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function directory(string $keyword = '', int $perPage = self::DIRECTORY_PER_PAGE)
    {
        $store = (new AdminStore)->getTable();
        $query = AdminStore::query()
            ->select($store.'.*')
            ->where($store.'.status', 1)
            ->where($store.'.id', '<>', (string) GP247_STORE_ID_ROOT)
            ->with('descriptions');
        $keyword = trim($keyword);
        if ($keyword !== '') {
            $desc = (new AdminStoreDescription)->getTable();
            $query->join($desc, function ($join) use ($desc, $store) {
                $join->on($desc.'.store_id', '=', $store.'.id')->where($desc.'.lang', gp247_get_locale());
            })->where($desc.'.name', 'like', '%'.$keyword.'%');
        }

        return $query->orderByDesc($store.'.created_at')->paginate($perPage)->withQueryString();
    }

    /**
     * Sellable single products per store — one grouped query for a whole page of cards.
     *
     * @param  array<int, string> $storeIds
     * @return array<string, int> store id => count (0 when none)
     */
    public static function productCounts(array $storeIds): array
    {
        $counts = array_fill_keys(array_map('strval', $storeIds), 0);
        if ($storeIds === []) {
            return $counts;
        }
        $rows = ShopProduct::whereIn('store_id', $storeIds)
            ->where('status', 1)
            ->where('approve', 1)
            ->where('kind', '<>', GP247_PRODUCT_GROUP)
            ->selectRaw('store_id, COUNT(*) as total')
            ->groupBy('store_id')
            ->pluck('total', 'store_id');
        foreach ($rows as $storeId => $total) {
            $counts[(string) $storeId] = (int) $total;
        }

        return $counts;
    }

    /**
     * Seller rating summaries for a set of stores (directory cards); empty when
     * the ProductRating plugin is not available.
     *
     * @param  array<int, string> $storeIds
     * @return array<string, array{total:int, average:float, distribution:array}>
     */
    public static function ratingSummaries(array $storeIds): array
    {
        $out = [];
        foreach ($storeIds as $storeId) {
            $summary = self::sellerRating((string) $storeId);
            if ($summary !== null) {
                $out[(string) $storeId] = $summary;
            }
        }

        return $out;
    }
}
