<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Middleware;

use CoStack\EasyRequestToken\Security\LockedSecurityObjects;
use CoStack\EasyRequestToken\Security\SecurityObjectsFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Symfony\Component\HttpFoundation\Cookie;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function htmlspecialchars;

readonly class ConvertPostLoginRedirectToHtmlRedirectMiddleware implements Middleware
{
    public function __construct(
        protected SecurityObjectsFactory $securityObjectsFactory,
    ) {}

    public function process(ServerRequest $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);

        if ($this->shouldConvert($request, $response)) {
            $uri = $response->getHeaderLine('Location');
            $absoluteUri = GeneralUtility::locationHeaderUrl($uri);
            $htmlAttrUri = htmlspecialchars($absoluteUri);
            $responseFactory = GeneralUtility::makeInstance(ResponseFactory::class);
            $newResponse = $responseFactory->createResponse();
            $newResponse->getBody()->write(
                <<<HTML
                <!DOCTYPE html>
                <html lang="en">
                    <head>
                        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                        <meta http-equiv="refresh" content="0;url={$htmlAttrUri}" />
                    <title>You will be redirected</title>
                    </head>
                    <body>
                        <p>
                            You should be redirected to <a href="{$htmlAttrUri}">{$htmlAttrUri}</a> instantly.
                        </p>
                    </body>
                </html>
                HTML,
            );
            $headers = $response->getHeaders();
            unset($headers['Location'], $headers['location']);
            foreach ($headers as $name => $values) {
                $newResponse = $newResponse->withAddedHeader($name, $values);
            }
            $response = $newResponse;
        }
        return $response;
    }

    protected function shouldConvert(ServerRequest $request, Response $response): bool
    {
        $cookieSameSite = $GLOBALS['TYPO3_CONF_VARS']['BE']['cookieSameSite'] ?? Cookie::SAMESITE_STRICT;
        if ($cookieSameSite !== Cookie::SAMESITE_STRICT) {
            return false;
        }
        if (!$response instanceof RedirectResponse) {
            return false;
        }
        if (!$GLOBALS['BE_USER'] instanceof BackendUserAuthentication) {
            return false;
        }
        $normalizedParams = $request->getAttribute('normalizedParams');
        if (!$normalizedParams instanceof NormalizedParams) {
            return false;
        }

        $queryParams = $request->getQueryParams();
        if (empty($queryParams['loginProvider'])) {
            return false;
        }
        if (1751929727 !== (int) $queryParams['loginProvider']) {
            return false;
        }

        $securityObjects = $this->securityObjectsFactory->getCurrentSecurityObjects('oauth2client/connect/init')
            ?? $this->securityObjectsFactory->getCurrentSecurityObjects('oauth2client/connect/init')
            ?? $this->securityObjectsFactory->getCurrentSecurityObjects('core/user-auth/be');
        if (!$securityObjects instanceof LockedSecurityObjects) {
            return false;
        }
        return true;
    }
}
