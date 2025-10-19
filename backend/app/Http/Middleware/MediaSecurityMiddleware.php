<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MediaSecurityMiddleware
{
    /**
     * Maximum file size (10MB)
     */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Maximum files per upload
     */
    private const MAX_FILES_PER_UPLOAD = 10;

    /**
     * Rate limiting: max uploads per minute per user
     */
    private const MAX_UPLOADS_PER_MINUTE = 20;

    /**
     * Allowed MIME types
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/avif',
        'image/bmp',
        'image/tiff',
        'image/svg+xml',
    ];

    /**
     * Blocked file extensions
     */
    private const BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'phtml',
        'jsp', 'asp', 'aspx', 'exe', 'bat', 'cmd', 'com',
        'scr', 'pif', 'jar', 'js', 'vb', 'vbs', 'wsf',
        'sh', 'py', 'pl', 'rb', 'cgi', 'htaccess', 'htpasswd',
    ];

    /**
     * Malicious file signatures (magic bytes)
     */
    private const MALICIOUS_SIGNATURES = [
        // PHP files
        "\x3f\x70\x68\x70", // <?php
        "\x3c\x3f\x70\x68\x70", // <?php
        "\x3c\x70\x68\x70", // <php

        // Executable files
        "\x4d\x5a", // MZ (Windows executable)
        "\x7f\x45\x4c\x46", // ELF (Linux executable)

        // Scripts
        "\x23\x21", // #! (shebang)
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to media-related routes
        if (!$this->isMediaRoute($request)) {
            return $next($request);
        }

        try {
            // Check authentication
            $this->validateAuthentication($request);

            // Check rate limiting
            $this->checkRateLimit($request);

            // Validate uploaded files
            if ($request->hasFile('file') || $request->hasFile('files')) {
                $this->validateUploadedFiles($request);
            }

            // Security scan
            $this->performSecurityScan($request);

            return $next($request);

        } catch (\Exception $e) {
            Log::warning('Media security validation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'route' => $request->route() ? $request->route()->getName() : 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Security validation failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Invalid request',
            ], 422);
        }
    }

    /**
     * Check if this is a media-related route
     */
    private function isMediaRoute(Request $request): bool
    {
        $routeName = $request->route() ? $request->route()->getName() : '';

        return str_contains($routeName, 'media') || str_contains($request->path(), 'media');
    }

    /**
     * Validate user authentication
     */
    private function validateAuthentication(Request $request): void
    {
        $user = Auth::user();

        if (!$user) {
            throw new \Exception('Authentication required for media operations');
        }

        // Check if user has permission to upload media
        if (!$user->can('upload_media')) {
            throw new \Exception('Insufficient permissions for media operations');
        }
    }

    /**
     * Check upload rate limiting
     */
    private function checkRateLimit(Request $request): void
    {
        $userId = Auth::id();
        $key = "media_uploads:{$userId}";
        $now = now();

        // Get current minute's upload count
        $uploads = Cache::get($key, []);

        // Filter uploads from current minute
        $currentMinute = $now->format('Y-m-d-H-i');
        $currentMinuteUploads = array_filter($uploads, function($timestamp) use ($currentMinute) {
            return date('Y-m-d-H-i', strtotime($timestamp)) === $currentMinute;
        });

        if (count($currentMinuteUploads) >= self::MAX_UPLOADS_PER_MINUTE) {
            throw new \Exception('Upload rate limit exceeded. Please try again later.');
        }

        // Add current upload timestamp
        $uploads[] = $now->toISOString();

        // Keep only last 100 uploads
        $uploads = array_slice($uploads, -100);

        Cache::put($key, $uploads, now()->addHour());
    }

    /**
     * Validate uploaded files
     */
    private function validateUploadedFiles(Request $request): void
    {
        $files = [];

        // Handle single file upload
        if ($request->hasFile('file')) {
            $files[] = $request->file('file');
        }

        // Handle multiple file upload
        if ($request->hasFile('files')) {
            $uploadedFiles = $request->file('files');

            if (is_array($uploadedFiles)) {
                $files = array_merge($files, $uploadedFiles);
            }
        }

        if (empty($files)) {
            return;
        }

        // Check file count
        if (count($files) > self::MAX_FILES_PER_UPLOAD) {
            throw new \Exception('Too many files uploaded. Maximum allowed: ' . self::MAX_FILES_PER_UPLOAD);
        }

        foreach ($files as $file) {
            $this->validateSingleFile($file);
        }
    }

    /**
     * Validate a single uploaded file
     */
    private function validateSingleFile(UploadedFile $file): void
    {
        // Check if file was uploaded successfully
        if (!$file->isValid()) {
            throw new \Exception('File upload failed: ' . $file->getErrorMessage());
        }

        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \Exception('File size exceeds maximum allowed size of ' . (self::MAX_FILE_SIZE / 1024 / 1024) . 'MB');
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            throw new \Exception('File type not allowed: ' . $mimeType);
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, self::BLOCKED_EXTENSIONS)) {
            throw new \Exception('File extension not allowed: ' . $extension);
        }

        // Validate actual file content
        $this->validateFileContent($file);

        // Check for malicious content
        $this->scanForMaliciousContent($file);
    }

    /**
     * Validate file content matches MIME type
     */
    private function validateFileContent(UploadedFile $file): void
    {
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        // For images, check if they're actually images
        if (str_starts_with($file->getMimeType(), 'image/')) {
            try {
                // Try to create image from file
                $image = imagecreatefromstring(file_get_contents($file->getPathname()));

                if (!$image) {
                    throw new \Exception('File content does not match file type');
                }

                imagedestroy($image);

            } catch (\Exception $e) {
                throw new \Exception('Invalid image file: ' . $e->getMessage());
            }
        }
    }

    /**
     * Scan file for malicious content
     */
    private function scanForMaliciousContent(UploadedFile $file): void
    {
        // Read first 1KB of file for signature scanning
        $handle = fopen($file->getPathname(), 'rb');
        $header = fread($handle, 1024);
        fclose($handle);

        // Check for malicious signatures
        foreach (self::MALICIOUS_SIGNATURES as $signature) {
            if (str_contains($header, $signature)) {
                throw new \Exception('Malicious content detected in file');
            }
        }

        // Check for embedded PHP code in images
        if (str_contains($mimeType, 'image/')) {
            $this->scanImageForPhp($header);
        }

        // Check for suspicious metadata
        $this->checkSuspiciousMetadata($file);
    }

    /**
     * Scan image files for embedded PHP code
     */
    private function scanImageForPhp(string $header): void
    {
        $phpPatterns = [
            '/<\?php/i',
            '/<\?=/i',
            '/<script\s+language\s*=\s*["\']php["\']/i',
            '/php\s*:/i',
            '/eval\s*\(/i',
            '/base64_decode\s*\(/i',
            '/system\s*\(/i',
            '/exec\s*\(/i',
        ];

        foreach ($phpPatterns as $pattern) {
            if (preg_match($pattern, $header)) {
                throw new \Exception('Suspicious PHP code detected in image');
            }
        }
    }

    /**
     * Check for suspicious metadata in files
     */
    private function checkSuspiciousMetadata(UploadedFile $file): void
    {
        // Check for double extensions (e.g., image.jpg.php)
        $originalName = strtolower($file->getClientOriginalName());

        if (preg_match('/\.(' . implode('|', self::BLOCKED_EXTENSIONS) . ')$/i', $originalName)) {
            throw new \Exception('Suspicious file extension detected');
        }

        // Check for hidden files
        if (str_starts_with($originalName, '.')) {
            throw new \Exception('Hidden files are not allowed');
        }

        // Check for extremely long filenames (potential path traversal)
        if (strlen($originalName) > 255) {
            throw new \Exception('Filename too long');
        }
    }

    /**
     * Perform comprehensive security scan
     */
    private function performSecurityScan(Request $request): void
    {
        // Check request headers for suspicious content
        $this->scanRequestHeaders($request);

        // Check for SQL injection patterns in request data
        $this->scanForSqlInjection($request);

        // Check for XSS patterns in request data
        $this->scanForXss($request);

        // Check for path traversal attempts
        $this->scanForPathTraversal($request);
    }

    /**
     * Scan request headers for suspicious content
     */
    private function scanRequestHeaders(Request $request): void
    {
        $suspiciousHeaders = [
            'X-Forwarded-For' => '/\.\.\//', // Path traversal
            'Referer' => '/javascript:/i', // JavaScript URLs
            'User-Agent' => '/<(script|iframe)/i', // Script injection
        ];

        foreach ($suspiciousHeaders as $header => $pattern) {
            $value = $request->header($header);

            if ($value && preg_match($pattern, $value)) {
                throw new \Exception("Suspicious content detected in {$header} header");
            }
        }
    }

    /**
     * Scan for SQL injection patterns
     */
    private function scanForSqlInjection(Request $request): void
    {
        $sqlPatterns = [
            '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b|\bCREATE\b|\bALTER\b)/i',
            '/(\bor\b|\band\b)\s+\d+\s*=\s*\d+/i',
            '/(\-\-|\#|\/\*|\*\/)/',
            '/(\bexec\b|\bexecute\b)/i',
        ];

        $inputData = array_merge(
            $request->query(),
            $request->request->all()
        );

        array_walk_recursive($inputData, function($value) use ($sqlPatterns) {
            if (is_string($value)) {
                foreach ($sqlPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        throw new \Exception('Potential SQL injection detected');
                    }
                }
            }
        });
    }

    /**
     * Scan for XSS patterns
     */
    private function scanForXss(Request $request): void
    {
        $xssPatterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/<object[^>]*>.*?<\/object>/is',
            '/<embed[^>]*>/i',
            '/expression\s*\(/i',
            '/vbscript:/i',
        ];

        $inputData = array_merge(
            $request->query(),
            $request->request->all()
        );

        array_walk_recursive($inputData, function($value) use ($xssPatterns) {
            if (is_string($value)) {
                foreach ($xssPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        throw new \Exception('Potential XSS attack detected');
                    }
                }
            }
        });
    }

    /**
     * Scan for path traversal attempts
     */
    private function scanForPathTraversal(Request $request): void
    {
        $pathPatterns = [
            '/\.\.\//',
            '/\.\.\\\\/',
            '/%2e%2e%2f/i',
            '/%2e%2e\\/i',
            '/\.\.%2f/i',
            '/\.\.\%5c/i',
        ];

        $inputData = array_merge(
            $request->query(),
            $request->request->all()
        );

        array_walk_recursive($inputData, function($value) use ($pathPatterns) {
            if (is_string($value)) {
                foreach ($pathPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        throw new \Exception('Path traversal attempt detected');
                    }
                }
            }
        });
    }

    /**
     * Log security events
     */
    private function logSecurityEvent(string $event, array $context = []): void
    {
        Log::warning('Media security event', array_merge([
            'event' => $event,
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ], $context));
    }

    /**
     * Get security statistics
     */
    public static function getSecurityStats(): array
    {
        return Cache::remember('media_security_stats', now()->addMinutes(5), function() {
            return [
                'total_uploads_today' => static::getUploadCount('today'),
                'blocked_uploads_today' => static::getBlockedCount('today'),
                'suspicious_files_today' => static::getSuspiciousCount('today'),
                'rate_limit_hits_today' => static::getRateLimitHits('today'),
            ];
        });
    }

    /**
     * Get upload count for time period
     */
    private static function getUploadCount(string $period): int
    {
        $key = "media_uploads_count:{$period}";

        return Cache::get($key, 0);
    }

    /**
     * Get blocked upload count
     */
    private static function getBlockedCount(string $period): int
    {
        $key = "media_uploads_blocked:{$period}";

        return Cache::get($key, 0);
    }

    /**
     * Get suspicious file count
     */
    private static function getSuspiciousCount(string $period): int
    {
        $key = "media_files_suspicious:{$period}";

        return Cache::get($key, 0);
    }

    /**
     * Get rate limit hits count
     */
    private static function getRateLimitHits(string $period): int
    {
        $key = "media_rate_limit_hits:{$period}";

        return Cache::get($key, 0);
    }
}
