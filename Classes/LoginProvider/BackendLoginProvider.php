<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\LoginProvider;

use CoStack\Oauth2Client\Provider\ProviderCollectionFactory;
use CoStack\Oauth2Client\Provider\ProviderScope;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface as LoginProvider;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewInterface as View;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\View\FluidViewAdapter;
use TYPO3\CMS\Fluid\View\StandaloneView;

use function array_unshift;

#[Autoconfigure(public: true)]
readonly class BackendLoginProvider implements LoginProvider
{
    use LoginProviderTemplatePaths;

    public function render(StandaloneView $view, PageRenderer $pageRenderer, LoginController $loginController)
    {
        throw new RuntimeException('Legacy interface implementation. Should not be called', 1752700300);
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     * @noinspection PhpUnused This method is new API
     */
    public function modifyView(ServerRequest $request, View $view): string
    {
        if ($view instanceof FluidViewAdapter) {
            $renderingContext = $view->getRenderingContext();
            if ($renderingContext instanceof RenderingContext) {
                $this->setTemplatePaths($renderingContext);

                $providerCollectionFactory = GeneralUtility::makeInstance(ProviderCollectionFactory::class);
                $providers = $providerCollectionFactory->getProviderCollection(ProviderScope::BE);

                $view->assign('providers', $providers->byHash);

                return 'Backend/Login';
            }
        }
        return 'Error';
    }
}
