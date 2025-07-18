<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Provider;

use function hash;

readonly class ProviderCollection
{
    /** @var array<string, Provider> */
    public array $byIdentifier;
    /** @var array<string, Provider> */
    public array $byHash;

    /**
     * @param array<Provider> $providers
     */
    public function __construct(array $providers)
    {
        $byIdentifier = $byHash = [];
        foreach ($providers as $provider) {
            $identifierHash = hash('sha256', $provider->identifier);
            $byHash[$identifierHash] = $byIdentifier[$provider->identifier] = $provider;
        }
        $this->byIdentifier = $byIdentifier;
        $this->byHash = $byHash;
    }

    public function has(string $identifierOrHash): bool
    {
        return isset($this->byIdentifier[$identifierOrHash]) || isset($this->byHash[$identifierOrHash]);
    }

    public function get(string $identifierOrHash): ?Provider
    {
        return $this->byIdentifier[$identifierOrHash] ?? $this->byHash[$identifierOrHash] ?? null;
    }
}
