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
        Schema::create('media_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained()->onDelete('cascade');
            $table->string('conversion_name'); // e.g., 'thumb', 'medium', 'webp', 'avif'
            $table->string('conversion_type'); // e.g., 'resize', 'format', 'optimize'
            $table->string('file_name');
            $table->string('mime_type');
            $table->string('path');
            $table->string('disk')->default('public');
            $table->bigInteger('size')->unsigned();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->json('conversion_options')->nullable(); // Store conversion parameters
            $table->string('cloud_url')->nullable(); // R2/Cloudflare URL for this conversion
            $table->string('cdn_url')->nullable(); // CDN URL for this conversion
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->decimal('compression_ratio', 5, 2)->nullable();
            $table->decimal('quality_score', 3, 2)->nullable(); // Image quality score 0-1
            $table->timestamps();

            // Indexes for performance
            $table->index(['media_id', 'conversion_name']);
            $table->index(['status']);
            $table->index(['disk', 'path']);
            $table->index(['generated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_conversions');
    }
};
