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
        Schema::create('bundle_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['bundle_id'], 'idx_bundle_items_bundle_id');
            $table->index(['product_variant_id'], 'idx_bundle_items_variant_id');
            $table->index(['sort_order'], 'idx_bundle_items_sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bundle_items');
    }
};