<?php

declare(strict_types=1);

use CoStack\Oauth2Client\Middleware\ConvertPostLoginRedirectToHtmlRedirectMiddleware;
use CoStack\Oauth2Client\Middleware\RegisterBackendUserSelectionLoginProviderMiddleware;

return [
    'backend' => [
        'co-stack/oauth2_client_nuevo/conver' => [
            'target' => ConvertPostLoginRedirectToHtmlRedirectMiddleware::class,
            'before' => [
                'co-stack/oauth2_client_nuevo/process',
            ],
            'after' => [
                'typo3/cms-backend/backend-routing',
            ],
        ],
        'co-stack/oauth2_client_nuevo/register_beuser_selection' => [
            'target' => RegisterBackendUserSelectionLoginProviderMiddleware::class,
            'before' => [],
            'after' => [
                'typo3/cms-core/response-propagation',
            ],
        ],
    ],
];
