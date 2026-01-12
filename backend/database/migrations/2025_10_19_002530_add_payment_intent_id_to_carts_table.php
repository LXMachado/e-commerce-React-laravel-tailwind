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
        Schema::table('carts', function (Blueprint $table) {
            $table->string('payment_intent_id')->nullable()->after('is_active');
            $table->index(['payment_intent_id'], 'idx_carts_payment_intent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex('idx_carts_payment_intent_id');
            $table->dropColumn('payment_intent_id');
        });
    }
};
