<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Provider;

use CoStack\EasyRequestToken\Security\LockedSecurityObjects;
use Psr\Http\Message\UriInterface as Uri;
use TYPO3\CMS\Backend\Routing\UriBuilder;

readonly class BackendCallbackUri implements CallbackUri
{
    public function __construct(
        public LockedSecurityObjects $securityObjects,
    ) {}

    public function build(UriBuilder $uriBuilder): Uri
    {
        $params = [
            // Required for TYPO3 to call our auth service directly after redirect
            'login_status' => 'login',

            // The auth service to call
            'loginProvider' => 1751929727,

            // This will trigger the default LoginController "Login failed" message when the authentication fails.
            'commandLI' => 'attempt',
        ];

        $params = $this->securityObjects->addSecurityObjectsToParams($params);

        return $uriBuilder->buildUriFromRoute('login', $params, UriBuilder::ABSOLUTE_URL);
    }
}
