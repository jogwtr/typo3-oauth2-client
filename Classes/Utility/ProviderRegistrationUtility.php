<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Utility;

use CoStack\Oauth2Client\Provider\ProviderScope;
use Exception;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Github;
use Omines\OAuth2\Client\Provider\Gitlab;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use TYPO3\CMS\Core\Core\Environment;

use function class_exists;

class ProviderRegistrationUtility
{
    /**
     * Helps you to properly register an oauth provider.
     *
     * @param string $identifier Unique identifier. Will never be shown but can be used to override settings later.
     * @param string $label Split label (LLL:[...]) or casual string. This is shown to the user.
     * @param ProviderScope $providerScope If the provider can be used fot BE and/or FE authentication.
     * @param array $providerOptions Array of options passed to your provider implementation ()
     * @param string $iconIdentifier The icon identifier which is shown e.g. in the Backend Login Button
     * @param class-string<AbstractProvider> $providerImplementation An alternative implementation (e.g. Gitlab)
     */
    public static function register(
        string $identifier,
        string $label,
        ProviderScope $providerScope,
        array $providerOptions,
        string $iconIdentifier = 'oauth2_client_nuevo_provider_generic',
        string $providerImplementation = AbstractProvider::class,
    ): void {
        $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['tx_oauth2client']['provider'][$identifier] = [
            'label' => $label,
            'scope' => $providerScope,
            'providerOptions' => $providerOptions,
            'iconIdentifier' => $iconIdentifier,
            'providerImplementation' => $providerImplementation,
        ];
    }

    public static function registerGitlab(
        string $identifier,
        ProviderScope $providerScope,
        array $providerOptions,
        string $label = 'LLL:EXT:oauth2_client_nuevo/Resources/Private/Language/locallang.xlf:provider.gitlab',
        string $iconIdentifier = 'oauth2_client_nuevo_provider_gitlab',
    ): void {
        if (!class_exists(Gitlab::class)) {
            if (Environment::getContext()->isDevelopment()) {
                throw new Exception(
                    'ProviderRegistrationUtility::registerKeycloak required omines/oauth2-gitlab',
                    1753173355,
                );
            }
            return;
        }
        self::register($identifier, $label, $providerScope, $providerOptions, $iconIdentifier, Gitlab::class);
    }

    public static function registerGithub(
        string $identifier,
        ProviderScope $providerScope,
        array $providerOptions,
        string $label = 'LLL:EXT:oauth2_client_nuevo/Resources/Private/Language/locallang.xlf:provider.github',
        string $iconIdentifier = 'oauth2_client_nuevo_provider_github',
    ): void {
        if (!class_exists(Github::class)) {
            if (Environment::getContext()->isDevelopment()) {
                throw new Exception(
                    'ProviderRegistrationUtility::registerKeycloak required league/oauth2-github',
                    1753172819,
                );
            }
            return;
        }
        self::register($identifier, $label, $providerScope, $providerOptions, $iconIdentifier, Github::class);
    }

    public static function registerKeycloak(
        string $identifier,
        ProviderScope $providerScope,
        array $providerOptions,
        string $label = 'LLL:EXT:oauth2_client_nuevo/Resources/Private/Language/locallang.xlf:provider.keycloak',
        string $iconIdentifier = 'oauth2_client_nuevo_provider_keycloak',
    ): void {
        if (!class_exists(Keycloak::class)) {
            if (Environment::getContext()->isDevelopment()) {
                throw new Exception(
                    'ProviderRegistrationUtility::registerKeycloak required stevenmaguire/oauth2-keycloak',
                    1753172806,
                );
            }
            return;
        }
        self::register($identifier, $label, $providerScope, $providerOptions, $iconIdentifier, Keycloak::class);
    }
}
