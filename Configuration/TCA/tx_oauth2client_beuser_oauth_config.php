<?php

declare(strict_types=1);

use CoStack\Oauth2Client\UserFunc\BackendProviderItemProvider;

return [
    'ctrl' => [
        'title' => 'BE User OAuth Provider Configuration',
        'label' => 'provider',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'versioningWS' => false,
        'rootLevel' => 1,
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'searchFields' => 'be_user,provider',
        'security' => [
            'ignoreWebMountRestriction' => true,
            'ignoreRootLevelRestriction' => true,
        ],
        'enablecolumns' => [
            'disabled' => 'disabled',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
    ],
    'palettes' => [
        'paletteDisabled' => [
            'showitem' => '
                 disabled
             ',
        ],
        'paletteAccess' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access',
            'showitem' => '
                 starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel,
                 endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel,
             ',
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    be_user,
                    provider,
                    identifier,
                    create_identifier,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;paletteDisabled,
                    --palette--;;paletteAccess,
            ',
        ],
    ],
    'columns' => [
        'be_user' => [
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'be_users',
            ],
        ],
        'provider' => [
            'label' => 'Provider',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'Please select',
                        'value' => '',
                    ],
                ],
                'itemsProcFunc' => BackendProviderItemProvider::class . '->getProvider',
                'dbFieldLength' => 255,
            ],
        ],
        'identifier' => [
            'label' => 'Identifier',
            'displayCond' => 'FIELD:identifier:REQ:true',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
            ],
        ],
        'create_identifier' => [
            'label' => 'Register',
            'displayCond' => [
                'AND' => [
                    'FIELD:identifier:REQ:false',
                    'FIELD:provider:REQ:true',
                ],
            ],
            'config' => [
                'type' => 'none',
                'renderType' => 'connectOauthProvider',
            ],
        ],
    ],
];
