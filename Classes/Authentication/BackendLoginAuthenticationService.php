<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Authentication;

use CoStack\EasyRequestToken\Http\NonceCookie;
use CoStack\EasyRequestToken\Http\RedirectResponse;
use CoStack\EasyRequestToken\Security\SecurityObjectsFactory;
use CoStack\Oauth2Client\Event\LoginRequestStarted;
use CoStack\Oauth2Client\Event\RedirectResponseCreated;
use CoStack\Oauth2Client\Provider\BackendCallbackUri;
use CoStack\Oauth2Client\Provider\ProviderCollectionFactory;
use CoStack\Oauth2Client\Provider\ProviderScope;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\AbstractAuthenticationService;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\PropagateResponseException;

#[Autoconfigure(public: true)]
class BackendLoginAuthenticationService extends AbstractAuthenticationService
{
    public function __construct(
        protected EventDispatcher $eventDispatcher,
        protected UriBuilder $uriBuilder,
        protected ProviderCollectionFactory $providerCollectionFactory,
        protected SecurityObjectsFactory $securityObjectsFactory,
    ) {}

    /**
     * @noinspection PhpUnused
     */
    public function getUser(): ?array
    {
        $request = $this->authInfo['request'] ?? null;
        if (!$request instanceof ServerRequest) {
            $this->logger->error(
                'Unexpected error. Request is not instanceof ServerRequestInterface',
                ['request' => $request],
            );
            return null;
        }

        if ('POST' !== $request->getMethod()) {
            $this->logger->debug('Request method must be POST for login requests');
            return null;
        }

        $parsedBody = $request->getParsedBody();
        if (empty($parsedBody['provider_identifier'])) {
            $this->logger->debug('Request body does contain provider_identifier. This is not a request for us.');
            return null;
        }
        $providerIdentifier = $parsedBody['provider_identifier'];

        $normalizedParams = $request->getAttribute('normalizedParams');
        if (!$normalizedParams instanceof NormalizedParams) {
            $this->logger->error('Unexpected error. Request does not have normalizedParams attribute.');
            return null;
        }

        $providerCollection = $this->providerCollectionFactory->getProviderCollection(ProviderScope::BE);
        if (!$providerCollection->has($providerIdentifier)) {
            $this->logger->error(
                'Unexpected error. The provider from the request token is not configured.',
                ['provider_identifier' => $providerIdentifier],
            );
            return null;
        }
        $provider = $providerCollection->get($providerIdentifier);

        $securityObjects = $this->securityObjectsFactory->create('core/user-auth/be', [
            'oauth2_client_action' => 'verify',
            'provider_identifier' => $providerIdentifier,
        ]);
        $securityObjects->params['state'] = $securityObjects->getSigningIdentifierName();
        $securityObjects = $securityObjects->lock();

        $sitePath = $normalizedParams->getSitePath();

        $event = new LoginRequestStarted($request, $securityObjects, $provider, $sitePath);
        $this->eventDispatcher->dispatch($event);

        $backendCallbackUri = new BackendCallbackUri($securityObjects);

        $authorizationUrl = $provider->getAuthorizationUrl($backendCallbackUri, $this->uriBuilder, $securityObjects);

        $nonceCookie = new NonceCookie($securityObjects->signingSecret, $sitePath);
        $redirectResponse = new RedirectResponse($authorizationUrl, $nonceCookie);

        $event = new RedirectResponseCreated($redirectResponse);
        $this->eventDispatcher->dispatch($event);

        throw new PropagateResponseException($redirectResponse);
    }
}
