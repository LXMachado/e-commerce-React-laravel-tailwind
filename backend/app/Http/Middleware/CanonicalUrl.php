<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class CanonicalUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only add canonical URLs to HTML responses (not API responses)
        if (!$this->shouldAddCanonicalUrl($request, $response)) {
            return $response;
        }

        $canonicalUrl = $this->generateCanonicalUrl($request);

        if ($canonicalUrl) {
            $this->addCanonicalUrlToResponse($response, $canonicalUrl);
        }

        return $response;
    }

    /**
     * Check if we should add canonical URL to this response
     */
    private function shouldAddCanonicalUrl(Request $request, Response $response): bool
    {
        // Skip API routes
        if ($request->is('api/*')) {
            return false;
        }

        // Only add to HTML responses
        $contentType = $response->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'text/html')) {
            return false;
        }

        // Skip if response already has canonical URL
        if ($response->getContent() && str_contains($response->getContent(), '<link rel="canonical"')) {
            return false;
        }

        return true;
    }

    /**
     * Generate canonical URL for the current request
     */
    private function generateCanonicalUrl(Request $request): ?string
    {
        $path = $request->path();
        $queryParams = $request->query();

        // Remove pagination and other non-canonical parameters
        $canonicalParams = $this->filterCanonicalParams($queryParams);

        // Generate base URL
        $url = url($path);

        // Add canonical query parameters if any
        if (!empty($canonicalParams)) {
            $url .= '?' . http_build_query($canonicalParams);
        }

        return $url;
    }

    /**
     * Filter out non-canonical query parameters
     */
    private function filterCanonicalParams(array $queryParams): array
    {
        $canonicalParams = [];

        // Keep only parameters that should be in canonical URL
        $allowedParams = [
            'category', // Category filtering
            'sort',     // Sorting options
        ];

        foreach ($queryParams as $key => $value) {
            if (in_array($key, $allowedParams)) {
                $canonicalParams[$key] = $value;
            }
        }

        return $canonicalParams;
    }

    /**
     * Add canonical URL to HTML response
     */
    private function addCanonicalUrlToResponse(Response $response, string $canonicalUrl): void
    {
        $content = $response->getContent();

        if (!$content) {
            return;
        }

        $canonicalTag = '<link rel="canonical" href="' . htmlspecialchars($canonicalUrl) . '">' . "\n";

        // Insert canonical tag into head section
        if (str_contains($content, '<head>') && str_contains($content, '</head>')) {
            $content = str_replace(
                '<head>',
                '<head>' . "\n" . $canonicalTag,
                $content
            );
        } else {
            // Fallback: add to beginning of content
            $content = $canonicalTag . $content;
        }

        $response->setContent($content);
        $response->header('X-Canonical-URL-Added', 'true');
    }
}
