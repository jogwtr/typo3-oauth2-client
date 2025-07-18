<?php

use CoStack\Oauth2Client\Authentication\BackendLoginAuthenticationService;
use CoStack\Oauth2Client\Authentication\BackendVerificationAuthenticationService;
use CoStack\Oauth2Client\Authentication\BackendUserSelectionAuthenticationService;
use CoStack\Oauth2Client\LoginProvider\BackendLoginProvider;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (!empty($GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['tx_oauth2client']['provider'])) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][1751929727] = [
        'provider' => BackendLoginProvider::class,
        'sorting' => 150,
        'iconIdentifier' => 'oauth2_client_nuevo_extension_icon',
        'label' => 'LLL:EXT:oauth2_client_nuevo/Resources/Private/Language/locallang.xlf:backend',
    ];

    ExtensionManagementUtility::addService(
        'oauth2_client_nuevo',
        'auth',
        BackendLoginAuthenticationService::class,
        [
            'title' => 'Backend User authentication',
            'description' => 'Authentication with oauth2.',
            'subtype' => 'getUserBE',
            'available' => true,
            'priority' => 77,
            'quality' => 77,
            'os' => '',
            'exec' => '',
            'className' => BackendLoginAuthenticationService::class,
        ],
    );

    ExtensionManagementUtility::addService(
        'oauth2_client_nuevo',
        'auth',
        BackendVerificationAuthenticationService::class,
        [
            'title' => 'Backend User authentication',
            'description' => 'Authentication with oauth2.',
            'subtype' => 'getUserBE,authUserBE',
            'available' => true,
            'priority' => 78,
            'quality' => 77,
            'os' => '',
            'exec' => '',
            'className' => BackendVerificationAuthenticationService::class,
        ],
    );

    ExtensionManagementUtility::addService(
        'oauth2_client_nuevo',
        'auth',
        BackendUserSelectionAuthenticationService::class,
        [
            'title' => 'Backend User authentication',
            'description' => 'Authentication with oauth2.',
            'subtype' => 'getUserBE,authUserBE',
            'available' => true,
            'priority' => 78,
            'quality' => 77,
            'os' => '',
            'exec' => '',
            'className' => BackendUserSelectionAuthenticationService::class,
        ],
    );
}
