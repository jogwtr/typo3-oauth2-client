<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Event;

use League\OAuth2\Client\Provider\ResourceOwnerInterface as ResourceOwner;

final readonly class ResourceOwnerReceived
{
    public function __construct(
        public ResourceOwner $resourceOwner,
    ) {}
}
