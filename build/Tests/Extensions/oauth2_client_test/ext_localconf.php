<?php

use GuzzleHttp\Client;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Waldhacker\Oauth2ClientTest\Backend\LoginProvider\Oauth2LoginProvider;
use Waldhacker\Oauth2ClientTest\Controller\Frontend\ManageProvidersController;

defined('TYPO3') || die();

(static function () {
    ExtensionUtility::configurePlugin(
        'oauth2ClientTest',
        'ManageProviders',
        [ManageProvidersController::class => 'list'],
        [ManageProvidersController::class => 'list']
    );

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][Oauth2LoginProvider::PROVIDER_ID] = [
        'provider' => Oauth2LoginProvider::class,
        'sorting' => 26,
        'iconIdentifier' => 'actions-key',
        'label' => 'LLL:EXT:oauth2_client_test/Resources/Private/Language/locallang_be.xlf:login.link',
    ];

    if (!isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['Waldhacker']['Oauth2ClientTest']['Http']['Client']['Middleware']['LogMiddleware']['writerConfiguration'])) {
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['Waldhacker']['Oauth2ClientTest']['Http']['Client']['Middleware']['LogMiddleware']['writerConfiguration'] = [
            LogLevel::DEBUG => [
                FileWriter::class => [
                    'logFile' => Environment::getVarPath() . '/log/typo3_requests.log'
                ],
            ],
        ];
    }

    foreach (($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client']['providers'] ?? []) as $identifier => $provider) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client']['providers'][$identifier]['collaborators']['httpClient'] = Client::class;
    }
})();
