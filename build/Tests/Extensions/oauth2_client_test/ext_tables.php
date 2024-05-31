<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Waldhacker\Oauth2ClientTest\Backend\UserSettingsModule\ManageProvidersButtonRenderer;

defined('TYPO3') || die();

(static function () {
    $GLOBALS['TYPO3_USER_SETTINGS']['columns']['tx_oauth2_test_client_configs'] = [
        'label' => '',
        'type' => 'user',
        'userFunc' => ManageProvidersButtonRenderer::class . '->render',
    ];

    ExtensionManagementUtility::addFieldsToUserSettings(
        'tx_oauth2_test_client_configs',
        'after:mfaProviders'
    );
})();
