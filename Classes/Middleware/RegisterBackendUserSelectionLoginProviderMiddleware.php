<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Middleware;

use CoStack\EasyRequestToken\Security\LockedSecurityObjects;
use CoStack\EasyRequestToken\Security\SecurityObjectsFactory;
use CoStack\Oauth2Client\LoginProvider\BackendUserSelectionLoginProvider;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

readonly class RegisterBackendUserSelectionLoginProviderMiddleware implements Middleware
{
    public function __construct(
        protected SecurityObjectsFactory $securityObjectsFactory,
    ) {}

    public function process(ServerRequest $request, RequestHandler $handler): Response
    {
        if ($this->shouldRegister($request)) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][1752791827] = [
                'provider' => BackendUserSelectionLoginProvider::class,
                'sorting' => 999,
                'iconIdentifier' => 'oauth2_client_nuevo_extension_icon',
                'label' => 'LLL:EXT:oauth2_client_nuevo/Resources/Private/Language/locallang.xlf:backend',
            ];
        }
        return $handler->handle($request);
    }

    protected function shouldRegister(ServerRequest $request): bool
    {
        $queryParams = $request->getQueryParams();
        if (empty($queryParams['loginProvider'])) {
            return false;
        }
        if (1752791827 !== (int) $queryParams['loginProvider']) {
            return false;
        }
        $securityObjects = $this->securityObjectsFactory->getCurrentSecurityObjects(
            BackendUserSelectionLoginProvider::SCOPE,
        );
        if (!$securityObjects instanceof LockedSecurityObjects) {
            return false;
        }
        return true;
    }
}
