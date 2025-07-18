<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\League\OAuth2;

use GuzzleHttp\ClientInterface as GuzzleClient;
use GuzzleHttp\Promise\PromiseInterface as Promise;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;

#[Autoconfigure(public: true)]
readonly class TYPO3HttpClientAdapter implements GuzzleClient
{
    public function __construct(
        protected GuzzleClientFactory $guzzleClientFactory,
    ) {}

    public function send(Request $request, array $options = []): Response
    {
        return $this->guzzleClientFactory->getClient()->send($request, $options);
    }

    public function sendAsync(Request $request, array $options = []): Promise
    {
        return $this->guzzleClientFactory->getClient()->sendAsync($request, $options);
    }

    public function request(string $method, $uri, array $options = []): Response
    {
        return $this->guzzleClientFactory->getClient()->request($method, $uri, $options);
    }

    public function requestAsync(string $method, $uri, array $options = []): Promise
    {
        return $this->guzzleClientFactory->getClient()->requestAsync($method, $uri, $options);
    }

    public function getConfig(?string $option = null): mixed
    {
        return $this->guzzleClientFactory->getClient()->getConfig($option);
    }
}
