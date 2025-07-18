<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Event;

final readonly class BackendUserQueryResult
{
    public function __construct(
        public false|array $row,
    ) {}
}
