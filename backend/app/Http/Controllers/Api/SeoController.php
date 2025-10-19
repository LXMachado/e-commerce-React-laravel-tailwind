<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SeoService;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    protected SeoService $seoService;

    public function __construct(SeoService $seoService)
    {
        $this->seoService = $seoService;
    }

    /**
     * Generate and return XML sitemap
     */
    public function sitemap(): Response
    {
        $sitemap = $this->seoService->generateSitemap();

        return response($sitemap, 200, [
            'Content-Type' => 'application/xml',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    /**
     * Generate and return robots.txt
     */
    public function robots(): Response
    {
        $robotsTxt = $this->seoService->generateRobotsTxt();

        return response($robotsTxt, 200, [
            'Content-Type' => 'text/plain',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    /**
     * Get SEO configuration
     */
    public function config(): Response
    {
        return response()->json([
            'success' => true,
            'data' => $this->seoService->getConfig(),
            'message' => 'SEO configuration retrieved successfully'
        ]);
    }

    /**
     * Clear SEO cache (admin function)
     */
    public function clearCache(): Response
    {
        $this->seoService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'SEO cache cleared successfully'
        ]);
    }
}
