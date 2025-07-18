<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addTCAcolumns('be_users', [
    'tx_oauth2client_oauth_configs' => [
        'label' => 'LLL:EXT:oauth2_client_nuevo/Resources/Private/Language/locallang_be.xlf:tca.be_users.columns.tx_oauth2client_oauth_configs.label',
        'exclude' => true,
        'config' => [
            'type' => 'inline',
            'foreign_table' => 'tx_oauth2client_beuser_oauth_config',
            'foreign_field' => 'be_user',
        ],
    ],
]);
ExtensionManagementUtility::addToAllTCAtypes('be_users', 'tx_oauth2client_oauth_configs', '', 'before:avatar');
