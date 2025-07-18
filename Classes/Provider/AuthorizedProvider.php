<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface as ResourceOwner;
use League\OAuth2\Client\Token\AccessTokenInterface as AccessToken;

readonly class AuthorizedProvider
{
    public function __construct(
        protected AccessToken $accessToken,
        protected AbstractProvider $provider,
    ) {}

    public function getResourceOwner(): ResourceOwner
    {
        return $this->provider->getResourceOwner($this->accessToken);
    }
}
