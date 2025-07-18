<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\LoginProvider;

use CoStack\EasyRequestToken\Security\LockedSecurityObjects;
use CoStack\EasyRequestToken\Security\SecurityObjectsFactory;
use CoStack\Oauth2Client\Authentication\BackendUserSelectionAuthenticationService;
use CoStack\Oauth2Client\Domain\Repository\BackendUserRepository;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface as LoginProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewInterface as View;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\View\FluidViewAdapter;
use TYPO3\CMS\Fluid\View\StandaloneView;

#[Autoconfigure(public: true)]
class BackendUserSelectionLoginProvider implements LoginProvider
{
    use LoginProviderTemplatePaths;

    public const SCOPE = 'oauth2client/select/beuser';
    public const ACTION = 'select/beuser';

    public function render(StandaloneView $view, PageRenderer $pageRenderer, LoginController $loginController)
    {
        throw new RuntimeException('Legacy interface implementation. Should not be called', 1752791854);
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     * @noinspection PhpUnused This method is new API
     */
    public function modifyView(ServerRequest $request, View $view): string
    {
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uri = $uriBuilder->buildUriFromRoute('login', ['loginProvider' => '1433416747']);
        $redirect = new RedirectResponse($uri);
        $redirectException = new PropagateResponseException($redirect);

        if ($view instanceof FluidViewAdapter) {
            $renderingContext = $view->getRenderingContext();
            if ($renderingContext instanceof RenderingContext) {
                /** @var SecurityObjectsFactory $securityObjectsFactory */
                $securityObjectsFactory = GeneralUtility::makeInstance(SecurityObjectsFactory::class);
                $securityObjects = $securityObjectsFactory->getCurrentSecurityObjects(self::SCOPE);
                if (!$securityObjects instanceof LockedSecurityObjects) {
                    throw $redirectException;
                }
                $receivedToken = $securityObjects->requestToken;
                if (empty($receivedToken->params['be_user_list'])) {
                    throw $redirectException;
                }

                $this->setTemplatePaths($renderingContext);

                /** @var BackendUserRepository $backendUserRepository */
                $backendUserRepository = GeneralUtility::makeInstance(BackendUserRepository::class);
                $beUsers = $backendUserRepository->findByUid($receivedToken->params['be_user_list']);

                $options = [];

                foreach ($beUsers as $beUser) {
                    $securityObjects = $securityObjectsFactory->create(
                        BackendUserSelectionAuthenticationService::SCOPE,
                        ['uid' => $beUser['uid']],
                    );
                    $securityObjects = $securityObjects->lock();
                    $options[] = [
                        'label' => $beUser['username'],
                        'value' => $securityObjects->toJson(),
                    ];
                }

                $view->assign('options', $options);

                return 'Backend/UserSelection';
            }
        }
        return 'Login';
    }
}
