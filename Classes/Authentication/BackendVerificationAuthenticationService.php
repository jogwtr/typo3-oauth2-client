<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Authentication;

use CoStack\EasyRequestToken\Http\NonceCookie;
use CoStack\EasyRequestToken\Http\RedirectResponse;
use CoStack\EasyRequestToken\Security\LockedSecurityObjects;
use CoStack\EasyRequestToken\Security\SecurityObjectsFactory;
use CoStack\Oauth2Client\Domain\Repository\BackendUserOauthConfigRepository;
use CoStack\Oauth2Client\Domain\Repository\BackendUserRepository;
use CoStack\Oauth2Client\Event\BackendUserAuthenticated;
use CoStack\Oauth2Client\Event\RedirectResponseCreated;
use CoStack\Oauth2Client\Event\ResourceOwnerReceived;
use CoStack\Oauth2Client\Event\VerifyRequestStarted;
use CoStack\Oauth2Client\Exception\AccessTokenRequestFailedException;
use CoStack\Oauth2Client\Exception\ResourceOwnerRequestFailedException;
use CoStack\Oauth2Client\LoginProvider\BackendUserSelectionLoginProvider;
use CoStack\Oauth2Client\Messaging\FlashMessageService;
use CoStack\Oauth2Client\Provider\BackendCallbackUri;
use CoStack\Oauth2Client\Provider\ProviderCollectionFactory;
use CoStack\Oauth2Client\Provider\ProviderScope;
use CoStack\Oauth2Client\Provider\SelectBeUserUri;
use Exception;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Throwable;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\AbstractAuthenticationService;
use TYPO3\CMS\Core\Core\RequestId;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_column;
use function count;
use function hash_equals;
use function is_array;
use function reset;

#[Autoconfigure(public: true)]
class BackendVerificationAuthenticationService extends AbstractAuthenticationService
{
    protected ?array $authenticatedUser = null;

    public function __construct(
        protected EventDispatcher $eventDispatcher,
        protected FlashMessageService $flashMessageService,
        protected UriBuilder $uriBuilder,
        protected BackendUserRepository $backendUserRepository,
        protected BackendUserOauthConfigRepository $backendUserOauthConfigRepository,
        protected ProviderCollectionFactory $providerCollectionFactory,
        protected SecurityObjectsFactory $securityObjectsFactory,
    ) {}

    /**
     * @throws ResourceOwnerRequestFailedException
     * @throws Throwable
     * @throws AccessTokenRequestFailedException
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

        if ('GET' !== $request->getMethod()) {
            $this->logger->debug('Request method must be GET for verification requests');
            return null;
        }

        $securityObjects = $this->securityObjectsFactory->getCurrentSecurityObjects('core/user-auth/be');
        if (!$securityObjects instanceof LockedSecurityObjects) {
            $this->logger->error('Unexpected error. Could not get security objects');
            return null;
        }

        $receivedToken = $securityObjects->requestToken;
        if (empty($receivedToken->params['oauth2_client_action'])) {
            $this->logger->debug('Request token params do not contain oauth2_client_action. Not an oauth redirect.');
            return null;
        }

        if ('verify' !== $receivedToken->params['oauth2_client_action']) {
            $this->logger->error(
                'Unexpected error. oauth2_client_action is not set to verify',
                ['oauth2_client_action' => $receivedToken->params['oauth2_client_action']],
            );
            return null;
        }

        if (empty($receivedToken->params['state'])) {
            $this->logger->error('Unexpected error. Request token params do not contain state.');
            return null;
        }
        $state = $receivedToken->params['state'];

        if (empty($receivedToken->params['provider_identifier'])) {
            $this->logger->error('Unexpected error. Request token params do not contain provider_identifier.');
            return null;
        }
        $providerIdentifier = $receivedToken->params['provider_identifier'];

        $providerCollection = $this->providerCollectionFactory->getProviderCollection(ProviderScope::BE);
        if (!$providerCollection->has($providerIdentifier)) {
            $this->logger->error(
                'Unexpected error. The provider from the request token is not configured.',
                ['provider_identifier' => $providerIdentifier],
            );
            return null;
        }
        $provider = $providerCollection->get($providerIdentifier);

        $queryParams = $request->getQueryParams();

        if (empty($queryParams['code'])) {
            $this->logger->error('Unexpected error. Request token params do not contain code.');
            return null;
        }
        $code = $queryParams['code'];

        if (empty($queryParams['state'])) {
            $this->logger->error('Unexpected error. Request params do not contain state.');
            return null;
        }
        if (!hash_equals($state, $queryParams['state'])) {
            $this->logger->error('Request state verification failed. Request token and params mismatch.');
            return null;
        }

        $event = new VerifyRequestStarted($request, $securityObjects, $provider, $code, $state);
        $this->eventDispatcher->dispatch($event);

        $backendCallbackUri = new BackendCallbackUri($securityObjects);

        try {
            $authorizedProvider = $provider->authorizeProvider($backendCallbackUri, $this->uriBuilder, $code);
        } catch (Throwable $exception) {
            $exception = new AccessTokenRequestFailedException($exception);
            $this->logger->error('Provider authorization failed.', ['exception' => $exception]);
            $this->flashMessageService->enqueue(
                'LLL:EXT:oauth2_client_nuevo/Resources/Private/Language/locallang.xlf:login.oauth_client_fail',
                'LLL:EXT:oauth2_client_nuevo/Resources/Private/Language/locallang.xlf:login.resource_owner_error',
                [GeneralUtility::makeInstance(RequestId::class), $exception->getCode()],
            );
            throw $exception;
        }

        try {
            $resourceOwner = $authorizedProvider->getResourceOwner();
        } catch (Throwable $exception) {
            $exception = new ResourceOwnerRequestFailedException($exception);
            $this->logger->error('Resource owner request failed.', ['exception' => $exception]);
            $this->flashMessageService->enqueue(
                'LLL:EXT:oauth2_client_nuevo/Resources/Private/Language/locallang.xlf:login.oauth_client_fail',
                'LLL:EXT:oauth2_client_nuevo/Resources/Private/Language/locallang.xlf:login.authorization_code_error',
                [GeneralUtility::makeInstance(RequestId::class), $exception->getCode()],
            );
            throw $exception;
        }

        $event = new ResourceOwnerReceived($resourceOwner);
        $this->eventDispatcher->dispatch($event);

        $oauthConfigs = $this->backendUserOauthConfigRepository->findByProviderAndResourceOwner(
            $provider,
            $resourceOwner,
        );

        switch (count($oauthConfigs)) {
            case 0:
                $backendUser = null;
                break;
            case 1:
                $oauthConfig = reset($oauthConfigs);
                $backendUser = $this->backendUserRepository->findOneByUid($oauthConfig['be_user']);
                break;
            default:
                $beUserUids = array_column($oauthConfigs, 'be_user');
                $this->logger->debug(
                    'Found multiple oauth configs, redirecting to user selection.',
                    ['be_user_uids' => $beUserUids],
                );
                $securityObjects = $this->securityObjectsFactory->create(BackendUserSelectionLoginProvider::SCOPE, [
                    'oauth2_client_action' => BackendUserSelectionLoginProvider::ACTION,
                    'be_user_list' => $beUserUids,
                ]);
                $securityObjects = $securityObjects->lock();

                $normalizedParams = $request->getAttribute('normalizedParams');
                if (!$normalizedParams instanceof NormalizedParams) {
                    throw new Exception('TBD');
                }
                $path = $normalizedParams->getSitePath();

                $uri = new SelectBeUserUri($securityObjects);

                $nonceCookie = new NonceCookie($securityObjects->signingSecret, $path);
                $response = new RedirectResponse($uri->build($this->uriBuilder), $nonceCookie);

                $event = new RedirectResponseCreated($response);
                $this->eventDispatcher->dispatch($event);

                throw new PropagateResponseException($response);
        }
        $this->logger->debug('Backend user query result.', ['backend_user' => $backendUser]);

        if (!is_array($backendUser)) {
            $this->flashMessageService->enqueue(
                'LLL:EXT:oauth2_client_nuevo/Resources/Private/Language/locallang.xlf:login.oauth_client_fail',
                'LLL:EXT:oauth2_client_nuevo/Resources/Private/Language/locallang.xlf:login.no_backend_user_found',
            );
            return null;
        }

        return $this->authenticatedUser = $backendUser;
    }

    /**
     * @noinspection PhpUnused
     */
    public function authUser(array $user): int
    {
        if (
            null !== $this->authenticatedUser
            && $this->authenticatedUser === $user
        ) {
            $event = new BackendUserAuthenticated($user);
            $this->eventDispatcher->dispatch($event);

            return 200;
        }
        return 100;
    }
}
