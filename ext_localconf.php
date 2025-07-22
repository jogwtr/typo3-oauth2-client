<?php

use CoStack\Oauth2Client\Authentication\BackendLoginAuthenticationService;
use CoStack\Oauth2Client\Authentication\BackendUserSelectionAuthenticationService;
use CoStack\Oauth2Client\Authentication\BackendVerificationAuthenticationService;
use CoStack\Oauth2Client\Authentication\FrontendAuthenticationService;
use CoStack\Oauth2Client\Controller\Frontend\LoginController;
use CoStack\Oauth2Client\LoginProvider\BackendLoginProvider;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

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
            'title' => 'OAuth2 Client Backend User Authentication - Login with selected provider',
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
            'title' => 'OAuth2 Client Backend User Authentication - Verify selected provider',
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
            'title' => 'OAuth2 Client Backend User Authentication - Log in with selected user',
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

    ExtensionManagementUtility::addService(
        'oauth2_client',
        'auth',
        FrontendAuthenticationService::class,
        [
            'title' => 'OAuth2 Client Frontend User Authentication - Login with selected provider',
            'description' => 'OAuth2 authentication for frontend users',
            'subtype' => 'getUserFE,authUserFE,processLoginDataFE',
            'available' => true,
            'priority' => 75,
            'quality' => 50,
            'os' => '',
            'exec' => '',
            'className' => FrontendAuthenticationService::class,
        ],
    );

    ExtensionUtility::configurePlugin(
        'oauth2_client_nuevo',
        'login',
        [
            LoginController::class => ['index', 'login'],
        ],
        [
            LoginController::class => ['index', 'login'],
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
    );
}
