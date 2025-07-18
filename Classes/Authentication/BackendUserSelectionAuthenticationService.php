<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Authentication;

use CoStack\EasyRequestToken\Security\SecurityObjectsFactory;
use CoStack\Oauth2Client\Domain\Repository\BackendUserRepository;
use CoStack\Oauth2Client\Event\BackendUserAuthenticated;
use CoStack\Oauth2Client\Event\LoginSelectedBackendUserRequestStarted;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Throwable;
use TYPO3\CMS\Core\Authentication\AbstractAuthenticationService;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

#[Autoconfigure(public: true)]
class BackendUserSelectionAuthenticationService extends AbstractAuthenticationService
{
    public const SCOPE = 'oauth2client/login/beuser';
    protected ?array $authenticatedUser = null;

    public function __construct(
        protected EventDispatcher $eventDispatcher,
        protected BackendUserRepository $backendUserRepository,
        protected SecurityObjectsFactory $securityObjectsFactory,
    ) {}

    /**
     * @noinspection PhpUnused
     */
    public function getUser(): ?array
    {
        $request = $this->authInfo['request'] ?? null;
        if (!$request instanceof ServerRequest) {
            $this->logger->error(
                'Unexpected error. Request is not instanceof ServerRequestInterface',
                ['request' => $request],
            );
            return null;
        }

        if ('POST' !== $request->getMethod()) {
            $this->logger->debug('Request method must be POST for backend user selection requests');
            return null;
        }

        $parsedBody = $request->getParsedBody();

        if (empty($parsedBody['selected_be_user'])) {
            $this->logger->error('Unexpected error. Request body does not contain selected_be_user.');
            return null;
        }

        $selectedBackendUser = $parsedBody['selected_be_user'];

        try {
            $securityObjects = $this->securityObjectsFactory->fromJson($selectedBackendUser);
        } catch (Throwable $exception) {
            $this->logger->error('Unexpected error. selected_be_user is not valid.', ['exception' => $exception]);
            return null;
        }

        if (self::SCOPE !== $securityObjects->requestToken->scope) {
            $this->logger->error('Unexpected error. Request token scope invalid.');
            return null;
        }

        $selectedBackendUser = (int) $securityObjects->requestToken->params['uid'];

        $event = new LoginSelectedBackendUserRequestStarted($request, $selectedBackendUser);
        $this->eventDispatcher->dispatch($event);

        $backendUser = $this->backendUserRepository->findOneByUid($selectedBackendUser);
        if (empty($backendUser)) {
            $this->logger->error(
                'Unexpected error. No backend user found for selected uid.',
                ['uid' => $selectedBackendUser],
            );
            return null;
        }

        return $this->authenticatedUser = $backendUser;
    }

    /**
     * @noinspection PhpUnused
     */
    public function authUser(array $user): int
    {
        if (
            null !== $this->authenticatedUser
            && $this->authenticatedUser === $user
        ) {
            $event = new BackendUserAuthenticated($user);
            $this->eventDispatcher->dispatch($event);

            return 200;
        }
        return 100;
    }
}
