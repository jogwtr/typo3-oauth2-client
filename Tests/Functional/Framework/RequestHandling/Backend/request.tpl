<?php

require '{documentRoot}/typo3/sysext/core/Classes/Core/SystemEnvironmentBuilder.php';
require '{documentRoot}/typo3conf/ext/oauth2_client/Tests/Functional/Framework/RequestHandling/AbstractRequestBootstrap.php';
require '{documentRoot}/typo3conf/ext/oauth2_client/Tests/Functional/Framework/RequestHandling/Backend/RequestBootstrap.php';
(new \Waldhacker\Oauth2Client\Tests\Functional\Framework\RequestHandling\Backend\RequestBootstrap('{documentRoot}', '{vendorPath}', {arguments}))->executeAndOutput();

?>
