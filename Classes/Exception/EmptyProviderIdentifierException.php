<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Exception;

use CoStack\Oauth2Client\OAuth2ClientException;
use JetBrains\PhpStorm\Pure;

final class EmptyProviderIdentifierException extends OAuth2ClientException
{
    public const CODE = 1752082085;
    private const MESSAGE = <<<'TXT'
        Invalid provider configuration. Empty identifier.
        Check the array $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['tx_oauth2client']['provider'].
        All keys must be non-empty.
        TXT;

    #[Pure]
    public function __construct()
    {
        parent::__construct(self::MESSAGE, self::CODE);
    }
}
