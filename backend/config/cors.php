<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Sanctum stores stateful domains as host names (without a scheme), but the
    | CORS layer expects fully qualified origins. Converting the configured
    | domains to http/https origins keeps credentialed requests working in
    | local development while still allowing overrides through the env file.
    |
    */
    'allowed_origins' => array_map(
        static function (string $domain): string {
            $trimmed = trim($domain);
            if ($trimmed === '') {
                return $trimmed;
            }

            if (str_contains($trimmed, '://')) {
                return $trimmed;
            }

            if ($trimmed === '::1') {
                return 'http://[::1]';
            }

            return sprintf('http://%s', $trimmed);
        },
        array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
            '%s,%s',
            'localhost,localhost:3000,localhost:3001,127.0.0.1,127.0.0.1:3000,127.0.0.1:8000,::1',
            Laravel\Sanctum\Sanctum::currentApplicationUrlWithPort()
        )))))
    ),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
