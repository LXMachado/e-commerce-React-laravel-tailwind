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
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained()->onDelete('cascade');
            $table->foreignId('shipping_method_id')->constrained()->onDelete('cascade');
            $table->decimal('min_weight', 8, 3); // kg, support up to 99999.999kg
            $table->decimal('max_weight', 8, 3)->nullable(); // null for open-ended ranges
            $table->integer('price'); // price in cents
            $table->string('currency', 3)->default('AUD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure weight ranges don't overlap for same zone/method combination
            $table->unique(['shipping_zone_id', 'shipping_method_id', 'min_weight']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
