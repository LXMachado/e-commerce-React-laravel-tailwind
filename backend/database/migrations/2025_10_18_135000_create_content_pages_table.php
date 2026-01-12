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
        Schema::create('content_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content');
            $table->string('excerpt', 500)->nullable();
            $table->boolean('is_published')->default(false);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['slug'], 'idx_content_pages_slug');
            $table->index(['is_published'], 'idx_content_pages_published');
            $table->index(['sort_order'], 'idx_content_pages_sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_pages');
    }
};