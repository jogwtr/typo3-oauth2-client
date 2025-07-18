<?php

use CoStack\Oauth2Client\Controller\Backend\ConnectController;

return [
    'oauth2client_connect_init' => [
        'path' => '/oauth2/connect/init',
        'target' => ConnectController::class . '::handleInitRequest',
    ],
    'oauth2client_connect_authorize' => [
        'path' => '/oauth2/connect/callback',
        'redirect' => [
            'enable' => true,
            'parameters' => [
                'oauth2-provider' => true,
                'action' => true,
                'code' => true,
                'state' => true,
            ],
        ],
        'target' => ConnectController::class . '::handleRequest',
    ],
];
