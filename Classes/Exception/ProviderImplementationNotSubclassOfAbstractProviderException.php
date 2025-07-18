<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Exception;

use CoStack\Oauth2Client\OAuth2ClientException;
use JetBrains\PhpStorm\Pure;
use League\OAuth2\Client\Provider\AbstractProvider;
use function sprintf;

class ProviderImplementationNotSubclassOfAbstractProviderException extends OAuth2ClientException
{
    public const CODE = 1752691584;
    private const MESSAGE = <<<'TXT'
        Invalid provider configuration. The providerImplementation of provider %s must be a class string which is a sub-class of %s, but %s is not.
        Check the value $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['tx_oauth2client']['provider']['%s']['providerImplementation'].
        TXT;
    public readonly string $hint;

    #[Pure]
    public function __construct(
        public readonly string $identifier,
        public readonly string $actual,
    ) {
        parent::__construct(sprintf(self::MESSAGE, $identifier, AbstractProvider::class, $actual), self::CODE);
    }
}
