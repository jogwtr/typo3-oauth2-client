<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Event;

use CoStack\EasyRequestToken\Security\LockedSecurityObjects;
use CoStack\Oauth2Client\Provider\Provider;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;

final readonly class LoginRequestStarted
{
    public function __construct(
        public ServerRequest $request,
        LockedSecurityObjects $securityObjects,
        public Provider $provider,
        public string $sitePath,
    ) {}
}
