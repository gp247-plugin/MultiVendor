<?php

namespace App\GP247\Plugins\MultiVendor\Catalogue;

/**
 * The marketplace's catalogue rule: every vendor product is filed under the
 * marketplace's own categories, brands and taxes (the root store), because the
 * shared storefront browses by them — while the product stays owned by the
 * vendor's store. Registered as a gp247/shop product reference store resolver, so
 * every screen that edits a product follows it: the vendor's product screen and
 * the marketplace admin's product screen alike.
 *
 * @aidlc-unit multi-vendor-pro
 * @aidlc-story US-multi-vendor-pro-moderation-queue
 * @aidlc-adr shop-admin_product-reference-store
 */
final class MarketplaceTaxonomy
{
    /** Key of this resolver in `gp247-config.shop.product_reference_store_resolvers`. */
    public const RESOLVER_KEY = 'MultiVendor';

    /**
     * Store whose taxonomy a product owned by `$ownerStoreId` references: the root
     * store for a vendor shop, no opinion for the root store itself (or when no
     * owner is known yet, e.g. an empty create form).
     *
     * WHY every non-root store: a MultiVendor site has no other kind — the plugin
     * refuses to run next to MultiStore, whose stores keep their own taxonomy.
     *
     * @param string $ownerStoreId Store that owns the product.
     * @return string|null
     */
    public static function referenceStore(string $ownerStoreId): ?string
    {
        $root = (string) GP247_STORE_ID_ROOT;
        if ($ownerStoreId === '' || $ownerStoreId === $root) {
            return null;
        }

        return $root;
    }
}
