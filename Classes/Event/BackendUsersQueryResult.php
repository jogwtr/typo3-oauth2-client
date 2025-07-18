<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Event;

final readonly class BackendUsersQueryResult
{
    public function __construct(
        array $rows,
    ) {}
}
