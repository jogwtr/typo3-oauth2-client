<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Provider;

use CoStack\EasyRequestToken\Security\SecurityObjects;
use CoStack\Oauth2Client\Controller\Frontend\LoginController;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class FrontendCallbackUri implements CallbackUri
{
    public function __construct(
        public SecurityObjects $securityObjects,
        protected UriBuilder $uriBuilder,
        protected int $pid,
    ) {}

    public function build(): Uri
    {
        $uri = $this->uriBuilder
            ->reset()
            ->setCreateAbsoluteUri(true)
            ->setTargetPageType($this->pid)
            ->uriFor(
                'verify',
                null,
                LoginController::class,
                'oauth2_client_nuevo',
                'login',
            );
        return Uri::fromAnyScheme($uri);
    }
}
