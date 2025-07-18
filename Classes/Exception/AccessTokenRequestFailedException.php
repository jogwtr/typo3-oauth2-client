<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Exception;

use CoStack\Oauth2Client\OAuth2ClientException;
use JetBrains\PhpStorm\Pure;
use Throwable;

final class AccessTokenRequestFailedException extends OAuth2ClientException
{
    public const CODE = 1752425558;
    public const MESSAGE = 'Could not get access token by authorization code';

    #[Pure]
    public function __construct(Throwable $previous)
    {
        parent::__construct(self::MESSAGE, self::CODE, $previous);
    }
}
