<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Controller\Frontend;

use CoStack\EasyRequestToken\Security\SecurityObjectsFactory;
use CoStack\Oauth2Client\Provider\FrontendCallbackUri;
use CoStack\Oauth2Client\Provider\ProviderCollectionFactory;
use CoStack\Oauth2Client\Provider\ProviderScope;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class LoginController extends ActionController
{
    public function __construct(
        protected readonly ProviderCollectionFactory $providerCollectionFactory,
        protected readonly SecurityObjectsFactory $securityObjectsFactory,
    ) {}

    public function indexAction(): ResponseInterface
    {
        $providerCollection = $this->providerCollectionFactory->getProviderCollection(ProviderScope::FE);

        $securityObjects = $this->securityObjectsFactory->create('oauth2client/fe/login');
        $securityObjects = $securityObjects->lock();

        $this->view->assign('providers', $providerCollection->byHash);
        $this->view->assign('requestToken', $securityObjects->requestToken);

        return $this->htmlResponse();
    }

    public function loginAction(string $providerIdentifier): ResponseInterface
    {
        $securityObjects = $this->securityObjectsFactory->getCurrentSecurityObjects('oauth2client/fe/login');

        $providerCollection = $this->providerCollectionFactory->getProviderCollection(ProviderScope::FE);
        $provider = $providerCollection->get($providerIdentifier);

        /** @var PageArguments $routing */
        $routing = $this->request->getAttribute('routing');
        $callbackUri = new FrontendCallbackUri($securityObjects, $this->uriBuilder, $routing->getPageId());

        $authorizationUrl = $provider->getAuthorizationUrl($callbackUri, $securityObjects);

        return $this->redirectToUri($authorizationUrl);
    }
}
