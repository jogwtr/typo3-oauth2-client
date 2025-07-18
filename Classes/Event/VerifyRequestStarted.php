<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Event;

use CoStack\EasyRequestToken\Security\LockedSecurityObjects;
use CoStack\Oauth2Client\Provider\Provider;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;

final readonly class VerifyRequestStarted
{
    public function __construct(
        ServerRequest $request,
        public LockedSecurityObjects $securityObjects,
        public Provider $provider,
        public string $code,
        public string $state,
    ) {}
}
