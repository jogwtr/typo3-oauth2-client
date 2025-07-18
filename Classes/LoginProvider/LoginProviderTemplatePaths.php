<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\LoginProvider;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

use function array_unshift;

trait LoginProviderTemplatePaths
{
    protected function setTemplatePaths(RenderingContextInterface $renderingContext): void
    {
        $templatePaths = $renderingContext->getTemplatePaths();

        $templateRootPaths = $templatePaths->getTemplateRootPaths();
        array_unshift($templateRootPaths, 'EXT:oauth2_client_nuevo/Resources/Private/Templates');
        $templatePaths->setTemplateRootPaths($templateRootPaths);

        $partialRootPaths = $templatePaths->getPartialRootPaths();
        array_unshift($partialRootPaths, 'EXT:oauth2_client_nuevo/Resources/Private/Partials');
        $templatePaths->setPartialRootPaths($partialRootPaths);

        $layoutRootPaths = $templatePaths->getLayoutRootPaths();
        array_unshift($layoutRootPaths, 'EXT:oauth2_client_nuevo/Resources/Private/Layouts');
        $templatePaths->setLayoutRootPaths($layoutRootPaths);
    }
}
