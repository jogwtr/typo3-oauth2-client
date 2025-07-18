<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Provider;

use CoStack\EasyRequestToken\Security\LockedSecurityObjects;
use Psr\Http\Message\UriInterface as Uri;
use TYPO3\CMS\Backend\Routing\UriBuilder;

readonly class ConnectCallbackUri implements CallbackUri
{
    public function __construct(
        public LockedSecurityObjects $securityObjects,
    ) {}

    public function build(UriBuilder $uriBuilder): Uri
    {
        $params = $this->securityObjects->addSecurityObjectsToParams();

        return $uriBuilder->buildUriFromRoute('oauth2client_connect_authorize', $params, UriBuilder::ABSOLUTE_URL);
    }
}
