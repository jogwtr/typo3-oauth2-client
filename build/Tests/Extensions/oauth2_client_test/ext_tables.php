<?php

defined('TYPO3') || die();

(static function () {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserSetting(
        'tx_oauth2_test_client_configs',
        [
            'label' => '',
            'config' => [
                'type' => 'user',
                'renderType' => \Waldhacker\Oauth2ClientTest\Backend\UserSettingsModule\ManageProvidersButtonRenderer::class . '->render',
            ],
        ],
        'after:mfaProviders'
    );
})();
