<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Provider;

use CoStack\EasyRequestToken\Security\LockedSecurityObjects;
use League\OAuth2\Client\Provider\AbstractProvider;
use SensitiveParameter;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function hash;

readonly class Provider
{
    public function __construct(
        public string $identifier,
        public string $label,
        public ProviderScope $scope,
        public string $iconIdentifier,
        public string $providerImplementation,
        #[SensitiveParameter] public array $providerOptions,
        public array $collaborators = [],
    ) {}

    public function getHashedIdentifier(): string
    {
        return hash('sha256', $this->identifier);
    }

    public function getAuthorizationUrl(CallbackUri $callbackUri, LockedSecurityObjects $securityObjects): Uri
    {
        $provider = $this->getOauthProvider($callbackUri);

        $uri = $provider->getAuthorizationUrl(['state' => $securityObjects->getSigningIdentifierName()]);

        return Uri::fromAnyScheme($uri);
    }

    public function authorizeProvider(CallbackUri $callbackUri, string $code): AuthorizedProvider
    {
        $provider = $this->getOauthProvider($callbackUri);

        $accessToken = $provider->getAccessToken('authorization_code', ['code' => $code]);

        return new AuthorizedProvider($accessToken, $provider);
    }

    protected function getOauthProvider(CallbackUri $callbackUri): AbstractProvider
    {
        $options = $this->providerOptions;
        $options['redirectUri'] = (string) $callbackUri->build();

        $collaborators = [];
        if (!empty($this->collaborators['grantFactory'])) {
            $collaborators['grantFactory'] = GeneralUtility::makeInstance($this->collaborators['grantFactory']);
        }
        if (!empty($this->collaborators['requestFactory'])) {
            $collaborators['requestFactory'] = GeneralUtility::makeInstance($this->collaborators['requestFactory']);
        }
        if (!empty($this->collaborators['httpClient'])) {
            $collaborators['httpClient'] = GeneralUtility::makeInstance($this->collaborators['httpClient']);
        }
        if (!empty($this->collaborators['optionProvider'])) {
            $collaborators['optionProvider'] = GeneralUtility::makeInstance($this->collaborators['optionProvider']);
        }

        return GeneralUtility::makeInstance($this->providerImplementation, $options, $collaborators);
    }
}
