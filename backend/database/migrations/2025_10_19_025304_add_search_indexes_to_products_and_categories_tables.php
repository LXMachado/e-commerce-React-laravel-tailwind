<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add search optimization indexes to products table
        Schema::table('products', function (Blueprint $table) {
            // Index for name-based searches (will use LIKE queries)
            $table->index(['name'], 'idx_products_name_search');

            // Index for description-based searches (will use LIKE queries)
            $table->index(['description'], 'idx_products_description_search');

            // Index for short_description searches
            $table->index(['short_description'], 'idx_products_short_desc_search');

            // Composite index for category filtering with active status
            $table->index(['is_active', 'created_at'], 'idx_products_active_created');

            // Additional price range index for better performance
            $table->index(['price', 'is_active'], 'idx_products_price_active');
        });

        // Add search optimization indexes to categories table
        Schema::table('categories', function (Blueprint $table) {
            // Index for name-based searches (will use LIKE queries)
            $table->index(['name'], 'idx_categories_name_search');

            // Index for description-based searches (will use LIKE queries)
            $table->index(['description'], 'idx_categories_description_search');

            // Composite index for active categories with parent filtering
            $table->index(['is_active', 'parent_id'], 'idx_categories_active_parent');

            // Index for sorting by name and active status
            $table->index(['name', 'is_active'], 'idx_categories_name_active');
        });

        // Add search indexes to product variants table
        Schema::table('product_variants', function (Blueprint $table) {
            // Index for SKU-based searches (will use LIKE queries)
            $table->index(['sku'], 'idx_variants_sku_search');

            // Composite index for price range queries with active status
            $table->index(['price', 'is_active'], 'idx_variants_price_active');

            // Index for stock-based filtering
            $table->index(['stock_quantity', 'is_active'], 'idx_variants_stock_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from products table
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_name_search');
            $table->dropIndex('idx_products_description_search');
            $table->dropIndex('idx_products_short_desc_search');
            $table->dropIndex('idx_products_active_created');
            $table->dropIndex('idx_products_price_active');
        });

        // Drop indexes from categories table
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('idx_categories_name_search');
            $table->dropIndex('idx_categories_description_search');
            $table->dropIndex('idx_categories_active_parent');
            $table->dropIndex('idx_categories_name_active');
        });

        // Drop indexes from product variants table
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex('idx_variants_sku_search');
            $table->dropIndex('idx_variants_price_active');
            $table->dropIndex('idx_variants_stock_active');
        });
    }
};
