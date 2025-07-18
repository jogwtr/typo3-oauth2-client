<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Event;

use Psr\Http\Message\ServerRequestInterface as ServerRequest;

class LoginSelectedBackendUserRequestStarted
{
    public function __construct(
        ServerRequest $request,
        int $selectedBackendUser,
    ) {}
}
