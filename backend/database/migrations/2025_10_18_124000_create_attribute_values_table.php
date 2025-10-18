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
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['attribute_id'], 'idx_attribute_values_attribute_id');
            $table->index(['sort_order'], 'idx_attribute_values_sort');

            $table->unique(['attribute_id', 'value'], 'unique_attribute_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};