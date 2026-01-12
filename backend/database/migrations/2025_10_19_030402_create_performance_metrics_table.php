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
        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_type'); // 'api_response', 'database_query', 'cache_operation', 'system_resource'
            $table->string('metric_name'); // Specific metric identifier
            $table->string('operation')->nullable(); // Operation being measured (e.g., 'search', 'upload', 'GET /api/products')
            $table->string('endpoint')->nullable(); // API endpoint for API metrics
            $table->string('method')->nullable(); // HTTP method for API metrics
            $table->decimal('value', 12, 4); // The measured value
            $table->string('unit'); // 'ms', 'bytes', 'percentage', 'count', etc.
            $table->decimal('threshold', 12, 4)->nullable(); // Performance threshold
            $table->enum('status', ['success', 'warning', 'error', 'critical'])->default('success');
            $table->json('context')->nullable(); // Additional context data
            $table->json('metadata')->nullable(); // Additional metadata (query plans, stack traces, etc.)
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id')->nullable();
            $table->decimal('memory_usage_mb', 10, 2)->nullable();
            $table->decimal('cpu_usage_percent', 5, 2)->nullable();
            $table->integer('cache_hits')->nullable();
            $table->integer('cache_misses')->nullable();
            $table->decimal('cache_hit_ratio', 5, 4)->nullable();
            $table->integer('database_queries_count')->nullable();
            $table->decimal('database_query_time_ms', 10, 4)->nullable();
            $table->timestamp('measured_at');
            $table->timestamps();

            // Indexes for performance and analysis
            $table->index(['metric_type', 'metric_name']);
            $table->index(['measured_at']);
            $table->index(['status']);
            $table->index(['endpoint', 'method']);
            $table->index(['user_id']);
            $table->index(['operation']);
            $table->index(['metric_type', 'measured_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_metrics');
    }
};
