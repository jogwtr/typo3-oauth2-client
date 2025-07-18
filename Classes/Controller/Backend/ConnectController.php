<?php

/**
 * @noinspection PhpInternalEntityUsedInspection
 */

declare(strict_types=1);

namespace CoStack\Oauth2Client\Controller\Backend;

use CoStack\EasyRequestToken\Http\NonceCookie;
use CoStack\EasyRequestToken\Http\RedirectResponse;
use CoStack\EasyRequestToken\Security\LockedSecurityObjects;
use CoStack\EasyRequestToken\Security\SecurityObjectsFactory;
use CoStack\Oauth2Client\Provider\ConnectCallbackUri;
use CoStack\Oauth2Client\Provider\ProviderCollectionFactory;
use CoStack\Oauth2Client\Provider\ProviderScope;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\Response as CoreResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use function hash_equals;
use function time;

#[Autoconfigure(public: true)]
readonly class ConnectController
{
    public const SCOPE_INIT = 'oauth2client/connect/init';
    public const SCOPE_VERIFY = 'oauth2client/connect/verify';

    public function __construct(
        protected UriBuilder $uriBuilder,
        protected ProviderCollectionFactory $providerCollectionFactory,
        protected HashService $hashService,
        protected SecurityObjectsFactory $securityObjectsFactory,
    ) {}

    public function handleInitRequest(ServerRequest $request): Response
    {
        $nullResponse = new CoreResponse();

        $normalizedParams = $request->getAttribute('normalizedParams');
        if (!$normalizedParams instanceof NormalizedParams) {
            return $nullResponse;
        }

        $receivedSecurityObjects = $this->securityObjectsFactory->getCurrentSecurityObjects(self::SCOPE_INIT);
        if (!$receivedSecurityObjects instanceof LockedSecurityObjects) {
            return $nullResponse;
        }

        $receivedToken = $receivedSecurityObjects->requestToken;
        $params = $receivedToken->params;
        if (empty($params['state'])) {
            return $nullResponse;
        }

        if (empty($params['oauth_config'])) {
            return $nullResponse;
        }

        if (empty($params['provider_identifier'])) {
            return $nullResponse;
        }
        $providerIdentifier = $params['provider_identifier'];

        $securityObjects = $this->securityObjectsFactory->create(self::SCOPE_VERIFY, [
            'provider_identifier' => $providerIdentifier,
            'oauth_config' => $params['oauth_config'],
        ]);
        $securityObjects = $securityObjects->lock();

        $callbackUri = new ConnectCallbackUri($securityObjects);

        $providerCollection = $this->providerCollectionFactory->getProviderCollection(ProviderScope::BE);
        $provider = $providerCollection->get($providerIdentifier);
        $authorizationUrl = $provider->getAuthorizationUrl($callbackUri, $this->uriBuilder, $securityObjects);
        $cookie = new NonceCookie($securityObjects->signingSecret, $normalizedParams->getSitePath());
        $redirectResponse = new RedirectResponse($authorizationUrl, $cookie);

        throw new PropagateResponseException($redirectResponse);
    }

    public function handleRequest(ServerRequest $request): Response
    {
        $response = new CoreResponse();

        if ('GET' !== $request->getMethod()) {
            $response->getBody()->write('wrong method');
            return $response;
        }

        $queryParams = $request->getQueryParams();
        if (empty($queryParams['state'])) {
            $response->getBody()->write('no query param state');
            return $response;
        }

        if (empty($queryParams['code'])) {
            $response->getBody()->write('no query param code');
            return $response;
        }
        $code = $queryParams['code'];

        $receivedSecurityObjects = $this->securityObjectsFactory->getCurrentSecurityObjects(self::SCOPE_VERIFY);
        if (!$receivedSecurityObjects instanceof LockedSecurityObjects) {
            $response->getBody()->write('fail SecurityObjects');
            return $response;
        }

        $params = $receivedSecurityObjects->requestToken->params;

        if (empty($params['state'])) {
            $response->getBody()->write('no request token state');
            return $response;
        }

        if (empty($params['oauth_config'])) {
            $response->getBody()->write('no request token oauth_config');
            return $response;
        }
        $oauthConfig = $params['oauth_config'];

        if (empty($params['provider_identifier'])) {
            $response->getBody()->write('no request token provider_identifier');
            return $response;
        }
        $providerIdentifier = $params['provider_identifier'];

        if (!hash_equals($params['state'], $queryParams['state'])) {
            $response->getBody()->write('state verification failed');
            return $response;
        }

        $callbackUri = new ConnectCallbackUri($receivedSecurityObjects);

        $providerCollectionFactory = GeneralUtility::makeInstance(ProviderCollectionFactory::class);
        $providerCollection = $providerCollectionFactory->getProviderCollection(ProviderScope::BE);
        $provider = $providerCollection->get($providerIdentifier);

        $authorizedProvider = $provider->authorizeProvider($callbackUri, $this->uriBuilder, $code);
        $resourceOwner = $authorizedProvider->getResourceOwner();

        $datamap = [
            'tx_oauth2client_beuser_oauth_config' => [
                $oauthConfig => [
                    'identifier' => $resourceOwner->getId(),
                ],
            ],
        ];

        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($datamap, []);
        $dataHandler->process_datamap();

        $path = PathUtility::getAbsoluteWebPath(
            GeneralUtility::getFileAbsFileName('EXT:oauth2_client_nuevo/Resources/Public/JavaScript/callback.js'),
        );
        $path .= '?_=' . time();
        $html = <<<HTML
            <html lang="en">
            <head>
                <script src="{$path}"></script>
                <title>Success</title>
            </head>
                <body>
                    <p>Success</p>
                </body>
            </html>
            HTML;
        $response->getBody()->write($html);

        return $response;
    }
}
