<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Provider;

use CoStack\EasyRequestToken\Security\LockedSecurityObjects;
use Psr\Http\Message\UriInterface as Uri;
use TYPO3\CMS\Backend\Routing\UriBuilder;

readonly class SelectBeUserUri implements CallbackUri
{
    public function __construct(
        public LockedSecurityObjects $securityObjects,
    ) {}

    public function build(UriBuilder $uriBuilder): Uri
    {
        $params = [
            // The auth service to call
            'loginProvider' => 1752791827,
        ];

        $params = $this->securityObjects->addSecurityObjectsToParams($params);

        return $uriBuilder->buildUriFromRoute('login', $params, UriBuilder::ABSOLUTE_URL);
    }
}
