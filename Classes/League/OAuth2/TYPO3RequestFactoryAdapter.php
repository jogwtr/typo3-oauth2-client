<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\League\OAuth2;

use GuzzleHttp\Psr7\Request;
use League\OAuth2\Client\Tool\RequestFactory;
use Psr\Http\Message\StreamInterface as Stream;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Http\RequestFactory as OriginalRequestFactory;
use TYPO3\CMS\Core\Http\Stream as CoreStream;
use function is_resource;
use function is_string;

#[Autoconfigure(public: true)]
class TYPO3RequestFactoryAdapter extends RequestFactory
{
    public function __construct(
        protected readonly OriginalRequestFactory $requestFactory,
    ) {}

    /**
     * Creates a PSR-7 Request instance.
     *
     * @param null|string $method HTTP method for the request.
     * @param null|string $uri URI for the request.
     * @param array $headers Headers for the message.
     * @param string|resource|Stream $body Message body.
     * @param string $version HTTP protocol version.
     *
     * @return Request
     */
    public function getRequest($method, $uri, array $headers = [], $body = null, $version = '1.1')
    {
        $request = $this->requestFactory->createRequest($method, $uri);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        if (is_string($body)) {
            $request->getBody()->write($body);
        } elseif (is_resource($body)) {
            $request = $request->withBody(new CoreStream($body));
        } elseif ($body instanceof Stream) {
            $request = $request->withBody($body);
        }
        return $request->withProtocolVersion($version);
    }
}
