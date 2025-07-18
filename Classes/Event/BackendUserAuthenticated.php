<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Event;

final readonly class BackendUserAuthenticated
{
    public function __construct(
        public array $user,
    ) {}
}
