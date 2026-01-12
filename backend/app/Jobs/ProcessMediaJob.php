<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\MediaService;
use App\Services\PerformanceMonitorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public $timeout = 300; // 5 minutes

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [30, 60, 120];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Media $media,
        public array $options = []
    ) {
        $this->onQueue('media-processing');
    }

    /**
     * Execute the job.
     */
    public function handle(MediaService $mediaService, PerformanceMonitorService $performanceMonitor): void
    {
        $startTime = microtime(true);

        try {
            Log::info('Starting media processing job', [
                'media_id' => $this->media->id,
                'file_name' => $this->media->file_name,
                'job_id' => $this->job->getJobId(),
            ]);

            // Process the media
            $mediaService->processMedia($this->media, $this->options);

            // Record performance metrics
            $executionTime = microtime(true) - $startTime;
            $performanceMonitor->monitorSearchPerformance(
                'media_processing',
                $executionTime,
                [
                    'media_id' => $this->media->id,
                    'file_size' => $this->media->size,
                    'mime_type' => $this->media->mime_type,
                    'conversions_count' => $this->media->conversions()->count(),
                ]
            );

            Log::info('Media processing completed successfully', [
                'media_id' => $this->media->id,
                'execution_time' => round($executionTime, 2),
                'conversions_generated' => $this->media->conversions()->count(),
            ]);

        } catch (Exception $e) {
            $executionTime = microtime(true) - $startTime;

            Log::error('Media processing job failed', [
                'media_id' => $this->media->id,
                'error' => $e->getMessage(),
                'execution_time' => round($executionTime, 2),
                'attempt' => $this->attempts(),
                'job_id' => $this->job->getJobId(),
            ]);

            // Record failed performance metrics
            $performanceMonitor->monitorSearchPerformance(
                'media_processing_failed',
                $executionTime,
                [
                    'media_id' => $this->media->id,
                    'error' => $e->getMessage(),
                    'attempt' => $this->attempts(),
                ]
            );

            // If this is the final attempt, mark media as failed
            if ($this->attempts() >= $this->tries) {
                $this->media->markAsFailed('Processing failed after ' . $this->tries . ' attempts: ' . $e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::critical('Media processing job permanently failed', [
            'media_id' => $this->media->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'job_id' => $this->job->getJobId(),
        ]);

        // Mark media as failed if not already done
        if ($this->media->status !== 'failed') {
            $this->media->markAsFailed('Job failed permanently: ' . $exception->getMessage());
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return $this->backoff;
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): DateTime
    {
        return now()->addMinutes(10);
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [
            new \Illuminate\Queue\Middleware\ThrottlesExceptions(3, 60), // Max 3 exceptions per minute
            new \Illuminate\Queue\Middleware\RateLimited('media-processing'), // Rate limiting
        ];
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'media:' . $this->media->id,
            'type:media_processing',
            'mime_type:' . $this->media->mime_type,
        ];
    }
}
