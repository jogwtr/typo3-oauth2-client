<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\UserFunc;

use CoStack\Oauth2Client\Provider\ProviderCollectionFactory;
use CoStack\Oauth2Client\Provider\ProviderScope;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;

#[Autoconfigure(public: true)]
readonly class BackendProviderItemProvider
{
    public function __construct(
        protected ProviderCollectionFactory $providerCollectionFactory,
    ) {}

    public function getProvider(array &$params): void
    {
        $providers = $this->providerCollectionFactory->getProviderCollection(ProviderScope::BE);
        foreach ($providers->byHash as $hash => $provider) {
            $selectItem = new SelectItem(
                'select',
                $provider->label,
                $hash,
                $provider->iconIdentifier,
            );
            $params['items'][] = $selectItem->toArray();
        }
    }
}
