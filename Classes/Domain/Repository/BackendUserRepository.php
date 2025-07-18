<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Domain\Repository;

use CoStack\Oauth2Client\Event\BackendUserQueryResult;
use CoStack\Oauth2Client\Event\BackendUsersQueryResult;
use CoStack\Oauth2Client\Provider\Provider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface as ResourceOwner;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

#[Autoconfigure(public: true)]
class BackendUserRepository
{
    public function __construct(
        protected ConnectionPool $connectionPool,
        protected EventDispatcher $eventDispatcher,
    ) {}

    public function findOneByUid(int $uid): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');
        $queryBuilder
            ->select('user.*')
            ->from('be_users', 'user')
            ->where(
                $queryBuilder->expr()->eq(
                    'user.uid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT),
                ),
            );
        $result = $queryBuilder->executeQuery();
        $row = $result->fetchAssociative();

        $event = new BackendUserQueryResult($row);
        $this->eventDispatcher->dispatch($event);

        return $row ?: null;
    }

    public function findOneByProviderAndResourceOwner(Provider $provider, ResourceOwner $resourceOwner): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');
        $queryBuilder
            ->select('user.*')
            ->from('be_users', 'user')
            ->innerJoin('user', 'tx_oauth2client_beuser_oauth_config', 'auth_config', 'user.uid = auth_config.be_user')
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
        $row = $result->fetchAssociative();

        $event = new BackendUserQueryResult($row);
        $this->eventDispatcher->dispatch($event);

        return $row ?: null;
    }

    public function findByUid(array $uids): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');
        $queryBuilder
            ->select('user.*')
            ->from('be_users', 'user')
            ->where(
                $queryBuilder->expr()->in(
                    'user.uid',
                    $queryBuilder->createNamedParameter($uids, Connection::PARAM_INT_ARRAY),
                ),
            );
        $result = $queryBuilder->executeQuery();
        $rows = $result->fetchAllAssociative();

        $event = new BackendUsersQueryResult($rows);
        $this->eventDispatcher->dispatch($event);

        return $rows;
    }
}
