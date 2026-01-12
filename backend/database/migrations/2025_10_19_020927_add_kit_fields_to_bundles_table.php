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
        Schema::table('bundles', function (Blueprint $table) {
            $table->string('kit_type')->nullable()->after('slug'); // 'weekender', 'base_camp', etc.
            $table->integer('base_weight_g')->nullable()->after('compare_at_price'); // Base weight in grams
            $table->json('available_options')->nullable()->after('base_weight_g'); // Available configuration options
            $table->json('default_configuration')->nullable()->after('available_options'); // Default configuration
            $table->string('sku_prefix')->nullable()->after('default_configuration'); // Prefix for SKU generation
            $table->json('weight_threshold_compatibility')->nullable()->after('sku_prefix'); // Weight compatibility info

            $table->index(['kit_type'], 'idx_bundles_kit_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bundles', function (Blueprint $table) {
            $table->dropColumn([
                'kit_type',
                'base_weight_g',
                'available_options',
                'default_configuration',
                'sku_prefix',
                'weight_threshold_compatibility'
            ]);
        });
    }
};
