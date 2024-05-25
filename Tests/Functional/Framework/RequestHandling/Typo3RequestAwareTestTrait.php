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

use Exception;
use GuzzleHttp\Cookie\SetCookie;
use PHPUnit\Util\PHP\AbstractPhpProcess;
use Psr\Http\Message\ResponseInterface;
use SebastianBergmann\Template\Template;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Testbase;

use function serialize;
use function unserialize;

use const PHP_EOL;

trait Typo3RequestAwareTestTrait
{
    public function fetchFrontendPageContens(
        InternalRequest $request,
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
        InternalRequest $request,
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

    public function buildGetRequest(?string $uri = null, array $cookieData = []): InternalRequest
    {
        return (new InternalRequest($uri))->withCookieParams($cookieData);
    }

    public function buildPostRequest(
        ?string $uri = null,
        array $postData = [],
        array $queryParameters = [],
        array $cookieData = [],
    ): InternalRequest {
        return $this->buildGetRequest($uri, $cookieData)
            ->withMethod('POST')
            ->withParsedBody($postData)
            ->withQueryParameters($queryParameters);
    }

    private function executeRequest(
        InternalRequest $request,
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

            $cookies = array_map(
                fn(string $cookie): SetCookie => SetCookie::fromString($cookie),
                $response->getHeader('Set-Cookie'),
            );
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
        InternalRequest $request,
        InternalRequestContext $requestContext,
        bool $isBackendRequest = false,
    ): array {
        $templateFile = $isBackendRequest
            ? __DIR__ . '/Backend/request.tpl'
            : __DIR__ . '/Frontend/request.tpl';

        $template = new Template($templateFile);

        $template->setVar([
            'request' => serialize($request),
            'context' => serialize($requestContext),
            'documentRoot' => $this->instancePath,
            'vendorPath' => (new Testbase())->getPackagesPath(),
        ]);

        $php = AbstractPhpProcess::factory();
        return $php->runJob($template->render());
    }

    private function reconstituteRequestResult(array $result): ResponseInterface
    {
        if (!empty($result['stderr'])) {
            self::fail('Response is erroneous: ' . PHP_EOL . $result['stderr']);
        }

        $data = json_decode($result['stdout'] ?? '', true);
        if ($data === false) {
            self::fail('Response is empty');
        }

        if (!empty($data['exception'])) {
            if (!$data['exception'] instanceof \Throwable) {
                throw new Exception(
                    'Got content in key "exception" as response from the subrequest, but it is not an exception',
                    1716628244,
                );
            }
            throw $data['exception'];
        }

        if (!empty($data['unexpectedOutput'])) {
            self::fail('Got unexpected output during sub request dispatching: ' . PHP_EOL . $data['unexpectedOutput']);
        }

        if (empty($data['response'])) {
            self::fail('Request was dispatched without error but response is empty');
        }

        $streamFactory = new StreamFactory();
        $stream = $streamFactory->createStream($data['body']);

        /** @var ResponseInterface $response */
        $response = unserialize($data['response']);
        return $response->withBody($stream);
    }
}
