<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Provider;

use CoStack\Oauth2Client\Exception\EmptyProviderIdentifierException;
use CoStack\Oauth2Client\Exception\MissingProviderOptionException;
use CoStack\Oauth2Client\Exception\ProviderImplementationNotSubclassOfAbstractProviderException;
use CoStack\Oauth2Client\Exception\ScopeNotInstanceOfProviderScopeException;
use CoStack\Oauth2Client\League\OAuth2\TYPO3HttpClientAdapter;
use CoStack\Oauth2Client\League\OAuth2\TYPO3RequestFactoryAdapter;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericProvider;
use Psr\Log\LoggerAwareInterface as LoggerAware;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Throwable;
use TYPO3\CMS\Core\Core\Environment;
use function is_subclass_of;
use function trim;

#[Autoconfigure(public: true)]
class ProviderCollectionFactory implements LoggerAware
{
    use LoggerAwareTrait;

    public function getProviderCollection(ProviderScope $providerScope): ProviderCollection
    {
        $providerSettings = $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['tx_oauth2client']['provider'] ?? [];
        $providers = [];
        foreach ($providerSettings as $identifier => $provider) {
            $provider['identifier'] = trim($identifier);
            try {
                $provider = $this->createProvider($providerScope, $provider);
                if (null !== $provider) {
                    $providers[] = $provider;
                }
            } catch (Throwable $exception) {
                $this->logger->error(
                    'Invalid provider configuration. Provider configuration is not an array.',
                    ['exception' => $exception],
                );
                if (Environment::getContext()->isDevelopment()) {
                    throw $exception;
                }
            }
        }
        return new ProviderCollection($providers);
    }

    /**
     * @throws EmptyProviderIdentifierException
     * @throws MissingProviderOptionException
     * @throws ScopeNotInstanceOfProviderScopeException
     * @throws ProviderImplementationNotSubclassOfAbstractProviderException
     */
    protected function createProvider(
        ProviderScope $providerScope,
        array $settings,
    ): ?Provider {
        $identifier = $settings['identifier'];
        if ('' === $identifier) {
            throw new EmptyProviderIdentifierException();
        }
        if (empty($settings['scope'])) {
            throw new MissingProviderOptionException($identifier, 'scope');
        }
        if (!$settings['scope'] instanceof ProviderScope) {
            throw new ScopeNotInstanceOfProviderScopeException($identifier, $settings['scope']);
        }
        if (!$settings['scope']->contains($providerScope)) {
            return null;
        }
        if (empty($settings['label'])) {
            throw new MissingProviderOptionException($identifier, 'label');
        }
        if (empty($settings['iconIdentifier'])) {
            throw new MissingProviderOptionException($identifier, 'iconIdentifier');
        }
        if (empty($settings['providerOptions'])) {
            throw new MissingProviderOptionException($identifier, 'providerOptions');
        }
        if (
            !empty($settings['providerImplementation'])
            && !is_subclass_of($settings['providerImplementation'], AbstractProvider::class)
        ) {
            throw new ProviderImplementationNotSubclassOfAbstractProviderException(
                $identifier,
                $settings['providerImplementation'],
            );
        }

        // Collaborator defaults
        $settings['collaborators'] ??= [];
        $settings['collaborators']['requestFactory'] ??= TYPO3RequestFactoryAdapter::class;
        $settings['collaborators']['httpClient'] ??= TYPO3HttpClientAdapter::class;

        return new Provider(
            $identifier,
            $settings['label'],
            $settings['scope'],
            $settings['iconIdentifier'],
            $settings['providerImplementation'] ??= GenericProvider::class,
            $settings['providerOptions'],
            $settings['collaborators'],
        );
    }
}
