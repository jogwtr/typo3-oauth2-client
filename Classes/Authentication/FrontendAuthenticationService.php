<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Authentication;

use TYPO3\CMS\Core\Authentication\AbstractAuthenticationService;

class FrontendAuthenticationService extends AbstractAuthenticationService
{
    public function getUser(): ?array
    {
        $request = $this->getRequest();
        $providerId = (string)$request->getAttribute('oauth2.requestedProvider', '');
        $isLoginController = $this->requestStates->isCurrentController(RequestStates::CONTROLLER_LOGIN, $request);

        $this->action = $isLoginController
        && $this->requestStates->isCurrentAction(RequestStates::ACTION_LOGIN_AUTHORIZE, $request)
            ? 'authorize'
            : (
            $isLoginController
            && $this->requestStates->isCurrentAction(RequestStates::ACTION_LOGIN_VERIFY, $request)
                ? 'verify'
                : 'invalid'
            );

        if ($this->action === 'authorize') {
            $this->authorize($providerId, $request);
        }
        if ($this->action === 'verify') {
            $code = (string)$request->getAttribute('oauth2.code', '');
            $state = (string)$request->getAttribute('oauth2.state', '');
            return $this->verify($providerId, $code, $state, $request);
        }

        return null;
    }

    public function authUser(): int
    {
        if ($this->action !== 'verify') {
            return 100;
        }

        if ($this->remoteUser instanceof ResourceOwnerInterface) {
            return 200;
        }

        return -100;
    }

    public function processLoginData(array &$loginData, string $_): bool
    {
        $loginData['uname'] = $loginData['uname'] ?? '';
        $loginData['uident'] = $loginData['uident'] ?? '';

        return true;
    }

    /**
     * @throws ImmediateResponseException
     * @throws SessionNotCreatedException
     */
    private function authorize(string $providerId, ServerRequestInterface $request): void
    {
        // no oauth2 login at all or invalid provider
        if (empty($providerId) || !$this->oauth2ProviderManager->hasFrontendProvider($providerId)) {
            $this->sessionManager->removeSessionData($request);
            return;
        }

        $authorizationUrl = $this->oauth2Service->buildGetResourceOwnerAuthorizationUrl(
            $providerId,
            $this->buildCallbackUri($providerId, $request),
            $request
        );

        $response = $this->responseFactory->createResponse(302)->withHeader('location', $authorizationUrl);
        $response = $this->sessionManager->appendOAuth2CookieToResponse($response, $request);
        throw new ImmediateResponseException($response, 1643006821);
    }

    /**
     * @throws MissingConfigurationException
     * @throws Exception
     * @throws SessionNotCreatedException
     */
    private function verify(string $providerId, string $code, string $state, ServerRequestInterface $request): ?array
    {
        if (
            empty($providerId)
            || empty($code)
            || empty($state)
            || !$this->oauth2ProviderManager->hasFrontendProvider($providerId)
        ) {
            return null;
        }

        $provider = $this->oauth2Service->buildGetResourceOwnerProvider(
            $state,
            $providerId,
            $this->buildCallbackUri($providerId, $request),
            $request
        );
        if ($provider === null) {
            return null;
        }
        $accessToken = $this->oauth2Service->buildGetResourceOwnerAccessToken(
            $provider,
            $code
        );
        if ($accessToken === null) {
            return null;
        }
        $this->remoteUser = $this->oauth2Service->getResourceOwner($provider, $accessToken);

        if ($this->remoteUser === null) {
            return null;
        }

        /** @var Site|null $site */
        $site = $this->siteService->getSite();
        $language = $this->siteService->getLanguage();
        if ($site === null || $language === null) {
            return null;
        }
        $siteConfiguration = $site->getConfiguration();
        $languageConfiguration = $language->toArray();
        $storagePid = empty($languageConfiguration['oauth2_storage_pid'])
            ? ($siteConfiguration['oauth2_storage_pid'] ?? null)
            : $languageConfiguration['oauth2_storage_pid'];

        if (empty($storagePid)) {
            throw new MissingConfigurationException(
                'Missing storage pid configuration for frontend users.
                 Please set a storage folder in your site configuration.',
                1646040939
            );
        }

        $typo3User = $this->frontendUserRepository->getUserByIdentity(
            $providerId,
            (string)$this->remoteUser->getId(),
            (int)$storagePid
        );

        /** @var FrontendUserLookupEvent $userLookupEvent */
        $userLookupEvent = $this->eventDispatcher->dispatch(
            new FrontendUserLookupEvent(
                $providerId,
                $provider,
                $accessToken,
                $this->remoteUser,
                $typo3User,
                $site,
                $language
            )
        );
        $typo3User = $userLookupEvent->getTypo3User();

        if ($typo3User === null) {
            unset($this->remoteUser);
        }

        return $typo3User;
    }

    private function buildCallbackUri(string $providerId, ServerRequestInterface $request): string
    {
        return $this->siteService->buildCallbackUri(
            [
                'oauth2-provider' => $providerId,
                // TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication->formfield_status
                'logintype' => 'login',
            ],
            $request
        );
    }

    private function getRequest(): ServerRequestInterface
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
        if (!($request instanceof ServerRequestInterface)) {
            throw new InvalidArgumentException(
                sprintf('Request must implement "%s"', ServerRequestInterface::class),
                1643446001
            );
        }
        return $request;
    }
}
