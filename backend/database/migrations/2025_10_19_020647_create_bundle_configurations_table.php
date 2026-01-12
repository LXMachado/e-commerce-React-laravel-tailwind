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
        Schema::create('bundle_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name')->nullable();
            $table->json('configuration_data'); // Stores selected options (espresso module, filter, fan, solar panel size)
            $table->decimal('total_price', 10, 2);
            $table->integer('total_weight_g');
            $table->string('sku')->unique();
            $table->string('share_token')->unique()->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('weight_compatibility')->nullable(); // Stores compatibility info (<5kg, 5-10kg, >10kg)
            $table->timestamps();

            $table->index(['bundle_id'], 'idx_bundle_configurations_bundle_id');
            $table->index(['user_id'], 'idx_bundle_configurations_user_id');
            $table->index(['sku'], 'idx_bundle_configurations_sku');
            $table->index(['share_token'], 'idx_bundle_configurations_share_token');
            $table->index(['is_active'], 'idx_bundle_configurations_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bundle_configurations');
    }
};
