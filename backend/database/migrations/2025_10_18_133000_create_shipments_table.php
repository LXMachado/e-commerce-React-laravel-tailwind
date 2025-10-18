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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('restrict');
            $table->string('tracking_number', 255)->nullable();
            $table->string('carrier', 100)->nullable();
            $table->enum('status', ['preparing', 'shipped', 'in_transit', 'delivered', 'failed'])->default('preparing');
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('address_id')->constrained();
            $table->timestamps();

            $table->index(['order_id'], 'idx_shipments_order_id');
            $table->index(['tracking_number'], 'idx_shipments_tracking');
            $table->index(['status'], 'idx_shipments_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};