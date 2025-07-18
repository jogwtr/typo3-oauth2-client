<?php

use CoStack\Oauth2Client\Backend\Form\RenderType\ConnectOauthProviderElement;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1752700799] = [
    'nodeName' => 'connectOauthProvider',
    'priority' => '70',
    'class' => ConnectOauthProviderElement::class,
];
