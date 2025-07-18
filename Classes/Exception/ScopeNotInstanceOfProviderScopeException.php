<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Exception;

use CoStack\Oauth2Client\OAuth2ClientException;
use JetBrains\PhpStorm\Pure;
use function gettype;
use function sprintf;

class ScopeNotInstanceOfProviderScopeException extends OAuth2ClientException
{
    public const CODE = 1752691342;
    private const MESSAGE = <<<'TXT'
        Invalid provider configuration. The scope of provider %s must be an instance of ProviderScope, %s given.
        Check the value $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['tx_oauth2client']['provider']['%s']['scope'].
        TXT;
    public readonly string $hint;

    #[Pure]
    public function __construct(
        public readonly string $identifier,
        public readonly mixed $actual,
    ) {
        parent::__construct(sprintf(self::MESSAGE, $identifier, gettype($actual)), self::CODE);
    }
}
