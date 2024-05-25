<?php
$classLoader = require_once '{vendorPath}/autoload.php';
$context = unserialize('{context}');
$request = unserialize('{request}');
$requestBootstrap = new \Waldhacker\Oauth2Client\Tests\Functional\Framework\RequestHandling\Backend\RequestBootstrap(
    '{documentRoot}',
    $classLoader,
    $context,
    $request
);
$requestBootstrap->executeAndOutput();
