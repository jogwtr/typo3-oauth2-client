<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'co-stack.com oauth2 client',
    'description' => 'TYPO3 OAuth2 Login Client (backend and frontend)',
    'category' => 'plugin',
    'author' => 'Oliver Eglseder',
    'author_email' => 'oliver.eglseder@co-stack.com',
    'author_company' => 'co-stack.com',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
