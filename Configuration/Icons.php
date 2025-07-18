<?php

use FriendsOfTYPO3\FontawesomeProvider\Imaging\IconProvider\FontawesomeIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;

return [
    'oauth2_client_nuevo_extension_icon' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:oauth2_client_nuevo/Resources/Public/Icons/Extension.svg',
    ],
    'oauth2_client_nuevo_backend' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:oauth2_client_nuevo/Resources/Public/Icons/blank.png',
    ],
    'oauth2_client_nuevo_provider_generic' => [
        'provider' => FontawesomeIconProvider::class,
        'name' => 'unlock-keyhole',
    ],
    'oauth2_client_nuevo_provider_gitlab' => [
        'provider' => FontawesomeIconProvider::class,
        'name' => 'gitlab',
    ],
    'oauth2_client_nuevo_provider_github' => [
        'provider' => FontawesomeIconProvider::class,
        'name' => 'github',
    ],
    'oauth2_client_nuevo_provider_keycloak' => [
        'provider' => FontawesomeIconProvider::class,
        'name' => 'key',
    ],
];
