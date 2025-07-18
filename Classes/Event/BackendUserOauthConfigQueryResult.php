<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Event;

final readonly class BackendUserOauthConfigQueryResult
{
    public function __construct(
        public array $rows,
    ) {}
}
