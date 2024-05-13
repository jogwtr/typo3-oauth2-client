<?php

declare(strict_types=1);

/*
 * This file is part of the OAuth2 Client extension for TYPO3
 * - (c) 2021 Waldhacker UG
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Waldhacker\Oauth2Client\Tests\Functional\Framework\RequestHandling;

use GuzzleHttp\Cookie\SetCookie;
use PHPUnit\Util\PHP\AbstractPhpProcess;
use Psr\Http\Message\ResponseInterface;
use SebastianBergmann\Template\Template;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Testbase;

trait Typo3RequestAwareTestTrait
{
    public function fetchFrontendPageContens(
        ExtendedInternalRequest $request,
        bool $followRedirects = true,
        InternalRequestContext $requestContext = null,
    ): array {
        $requestContext = $requestContext ?? (new InternalRequestContext());
        $responseData = $this->executeRequest($request, $requestContext, false, $followRedirects);

        return [
            'response' => $responseData['response'],
            'cookieData' => $responseData['cookieData'],
            'pageMarkup' => (string) $responseData['response']->getBody(),
        ];
    }

    public function fetchBackendPageContens(
        ExtendedInternalRequest $request,
        bool $followRedirects = true,
        InternalRequestContext $requestContext = null,
    ): array {
        $requestContext = $requestContext ?? (new InternalRequestContext());
        $responseData = $this->executeRequest($request, $requestContext, true, $followRedirects);

        return [
            'response' => $responseData['response'],
            'cookieData' => $responseData['cookieData'],
            'pageMarkup' => (string) $responseData['response']->getBody(),
        ];
    }

    public function buildGetRequest(?string $uri = null, array $cookieData = []): ExtendedInternalRequest
    {
        return (new ExtendedInternalRequest($uri))->withCookieParams($cookieData);
    }

    public function buildPostRequest(
        ?string $uri = null,
        array $postData = [],
        array $queryParameters = [],
        array $cookieData = [],
    ): ExtendedInternalRequest {
        return $this->buildGetRequest($uri, $cookieData)
            ->withMethod('POST')
            ->withParsedBody($postData)
            ->withQueryParameters($queryParameters);
    }

    private function executeRequest(
        ExtendedInternalRequest $request,
        InternalRequestContext $requestContext = null,
        bool $isBackendRequest = false,
        bool $followRedirects = true,
    ): array {
        $requestContext = $requestContext ?? (new InternalRequestContext());

        $cookieData = $request->getCookieParams();
        $locationHeaders = [];
        do {
            $result = $this->retrieveRequestResult($request, $requestContext, $isBackendRequest);

            $response = $this->reconstituteRequestResult($result);
            $locationHeader = $response->getHeaderLine('location');
            if (in_array($locationHeader, $locationHeaders, true)) {
                self::fail(
                    implode(
                        LF . '* ',
                        array_merge(
                            ['Redirect loop detected:'],
                            $locationHeaders,
                            [$locationHeader],
                        ),
                    ),
                );
            }
            $locationHeaders[] = $locationHeader;

            $cookies = array_map(fn(string $cookie): SetCookie => SetCookie::fromString($cookie),
                $response->getHeader('Set-Cookie'));
            $cookieData = array_filter(
                array_replace_recursive(
                    $cookieData,
                    array_combine(
                        array_map(fn(SetCookie $cookie): string => $cookie->getName(), $cookies),
                        array_map(fn(SetCookie $cookie): string => $cookie->getValue(), $cookies),
                    ),
                ),
                fn(string $value): bool => $value !== 'deleted',
            );

            $request = $this->buildGetRequest($locationHeader, $cookieData);
        } while ($followRedirects && !empty($locationHeader));

        return [
            'response' => $response,
            'cookieData' => $cookieData,
        ];
    }

    private function retrieveRequestResult(
        ExtendedInternalRequest $request,
        InternalRequestContext $requestContext,
        bool $isBackendRequest = false,
    ): array {
        $arguments = [
            'request' => json_encode($request),
            'context' => json_encode($requestContext),
        ];

        $templateFile = $isBackendRequest
            ? __DIR__ . '/Backend/request.tpl'
            : __DIR__ . '/Frontend/request.tpl';

        $template = new Template($templateFile);

        $template->setVar([
            'arguments' => var_export($arguments, true),
            'documentRoot' => $this->instancePath,
            'originalRoot' => ORIGINAL_ROOT,
            'vendorPath' => (new Testbase())->getPackagesPath(),
        ]);

        $php = AbstractPhpProcess::factory();
        return $php->runJob($template->render());
    }

    private function reconstituteRequestResult(array $result): ResponseInterface
    {
        if (!empty($result['stderr'])) {
            $this->fail('Response is erroneous: ' . LF . $result['stderr']);
        }

        $data = json_decode($result['stdout'] ?? '', true);
        if ($data === false) {
            $this->fail('Response is empty: ' . LF . $result['stdout'] ?? '');
        }

        if ($data['status'] === 'failure') {
            try {
                $exception = new $data['exception']['type'](
                    $data['exception']['message'],
                    $data['exception']['code'],
                );
            } catch (\Throwable $throwable) {
                $exception = new \Exception(
                    (string) $data['exception']['message'],
                    (int) $data['exception']['code'],
                    (string) $data['exception']['type'],
                );
            }
            throw $exception;
        }

        if (($data['content'] ?? null) === null) {
            self::fail('Response is empty: ' . LF . $data);
        }

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();
        $response->getBody()->write($data['content']);
        foreach ($data['headers'] as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        return $response;
    }
}
