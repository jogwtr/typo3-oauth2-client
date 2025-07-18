<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Domain\Repository;

use CoStack\Oauth2Client\Event\BackendUserOauthConfigQueryResult;
use CoStack\Oauth2Client\Provider\Provider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface as ResourceOwner;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

class BackendUserOauthConfigRepository
{
    public function __construct(
        protected ConnectionPool $connectionPool,
        protected EventDispatcher $eventDispatcher,
    ) {}

    public function findByProviderAndResourceOwner(Provider $provider, ResourceOwner $resourceOwner): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');
        $queryBuilder
            ->select('auth_config.*')
            ->from('tx_oauth2client_beuser_oauth_config', 'auth_config')
            ->where(
                $queryBuilder->expr()->eq(
                    'auth_config.provider',
                    $queryBuilder->createNamedParameter($provider->getHashedIdentifier()),
                ),
                $queryBuilder->expr()->eq(
                    'auth_config.identifier',
                    $queryBuilder->createNamedParameter($resourceOwner->getId()),
                ),
            );
        $result = $queryBuilder->executeQuery();
        $rows = $result->fetchAllAssociative();

        $event = new BackendUserOauthConfigQueryResult($rows);
        $this->eventDispatcher->dispatch($event);

        return $rows;
    }
}
