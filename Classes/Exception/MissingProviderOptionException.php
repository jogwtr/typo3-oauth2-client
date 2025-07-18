<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Exception;

use CoStack\Oauth2Client\OAuth2ClientException;
use JetBrains\PhpStorm\Pure;
use function sprintf;

final class MissingProviderOptionException extends OAuth2ClientException
{
    public const CODE = 1752425161;
    private const MESSAGE = <<<'TXT'
        Invalid provider configuration. The provider "%1$s" misses the option "%2$s".
        Check the array $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['tx_oauth2client']['provider']['%1$s'].
        It must contain the key "%2$s".
        TXT;
    public readonly string $hint;

    #[Pure]
    public function __construct(
        public readonly string $identifier,
        public readonly string $optionName,
    ) {
        parent::__construct(sprintf(self::MESSAGE, $identifier, $optionName), self::CODE);
    }
}
