<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    /*
     * Document only the application API routes.
     */
    'api_path' => 'api',

    /*
     * Export destination for an OpenAPI document generated via `php artisan scramble:export`.
     */
    'export_path' => 'api.json',

    'info' => [
        'version' => '1.0.0',
        'description' => 'Patient Reported Outcomes (PRO) API documentation for the TTI backend assessment.',
    ],

    'ui' => [
        'title' => 'TTI PRO API Docs',
        'theme' => 'light',
        'hide_try_it' => false,
        'hide_schemas' => false,
        'layout' => 'responsive',
    ],

    /*
     * Keep generated docs limited to local / non-production access by using Scramble's default middleware.
     */
    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],
];
