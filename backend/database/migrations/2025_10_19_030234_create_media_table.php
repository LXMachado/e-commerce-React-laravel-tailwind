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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type');
            $table->string('path');
            $table->string('disk')->default('public');
            $table->string('file_hash', 64)->nullable();
            $table->bigInteger('size')->unsigned();
            $table->json('metadata')->nullable(); // Store additional file metadata
            $table->string('alt')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('caption')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('cloud_url')->nullable(); // R2/Cloudflare URL
            $table->string('cdn_url')->nullable(); // CDN optimized URL
            $table->timestamp('optimized_at')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->enum('processing_status', ['queued', 'processing', 'completed', 'failed'])->default('queued');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('conversion_sizes')->nullable(); // Store available conversion sizes
            $table->boolean('is_optimized')->default(false);
            $table->decimal('optimization_ratio', 5, 2)->nullable(); // Compression ratio achieved
            $table->timestamps();

            // Indexes for performance
            $table->index(['disk', 'path']);
            $table->index(['status', 'processing_status']);
            $table->index(['uploaded_by']);
            $table->index(['created_at']);
            $table->index(['mime_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
