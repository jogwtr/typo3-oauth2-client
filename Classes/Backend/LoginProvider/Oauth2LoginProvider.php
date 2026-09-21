<?php

declare(strict_types=1);

/*
 * This file is part of the OAuth2 Client extension for TYPO3
 * - (c) 2021 waldhacker UG (haftungsbeschränkt)
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Waldhacker\Oauth2Client\Backend\LoginProvider;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\View\ViewInterface;
use Waldhacker\Oauth2Client\Service\Oauth2ProviderManager;

class Oauth2LoginProvider implements LoginProviderInterface
{
    public const PROVIDER_ID = '1616569531';

    public function __construct(
        private readonly Oauth2ProviderManager $oauth2ProviderManager,
        private readonly ExtensionConfiguration $extensionConfiguration
    ) {
    }

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    public function render($view, $pageRenderer, $loginController): void
    {
        $extensionConfiguration = $this->extensionConfiguration->get('oauth2_client');

        if (
            method_exists($view, 'setLayoutRootPaths')
            && method_exists($view, 'getLayoutRootPaths')
            && method_exists($view, 'setTemplateRootPaths')
            && method_exists($view, 'getTemplateRootPaths')
            && method_exists($view, 'setPartialRootPaths')
            && method_exists($view, 'getPartialRootPaths')
        ) {
            $view->setLayoutRootPaths(array_merge(
                $view->getLayoutRootPaths(),
                ['EXT:oauth2_client/Resources/Private/Layouts/Backend/'],
                $extensionConfiguration['view']['layoutRootPaths'] ?? []
            ));

            $view->setTemplateRootPaths(array_merge(
                $view->getTemplateRootPaths(),
                ['EXT:oauth2_client/Resources/Private/Templates/Backend/'],
                $extensionConfiguration['view']['templateRootPaths'] ?? []
            ));

            $view->setPartialRootPaths(array_merge(
                $view->getPartialRootPaths(),
                ['EXT:oauth2_client/Resources/Private/Partials/Backend/'],
                $extensionConfiguration['view']['partialRootPaths'] ?? []
            ));
        }

        if (method_exists($view, 'setTemplate')) {
            $view->setTemplate($extensionConfiguration['view']['template'] ?? 'Oauth2LoginProvider');
        }

        $this->assignProviders($view);
    }

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    public function modifyView(
        ServerRequestInterface $request,
        ViewInterface $view
    ): string {
        $extensionConfiguration = $this->extensionConfiguration->get('oauth2_client');
        $this->addTemplatePaths($view, $extensionConfiguration);
        $this->assignProviders($view);

        return $extensionConfiguration['view']['template'] ?? 'Backend/Oauth2LoginProvider';
    }

    private function assignProviders($view): void
    {
        if (method_exists($view, 'assign')) {
            $view->assign('providers', $this->oauth2ProviderManager->getConfiguredBackendProviders());
        }
    }

    private function addTemplatePaths(ViewInterface $view, array $extensionConfiguration): void
    {
        if (!method_exists($view, 'getRenderingContext')) {
            return;
        }

        $templatePaths = $view->getRenderingContext()->getTemplatePaths();
        if (
            !method_exists($templatePaths, 'getTemplateRootPaths')
            || !method_exists($templatePaths, 'setTemplateRootPaths')
            || !method_exists($templatePaths, 'getLayoutRootPaths')
            || !method_exists($templatePaths, 'setLayoutRootPaths')
            || !method_exists($templatePaths, 'getPartialRootPaths')
            || !method_exists($templatePaths, 'setPartialRootPaths')
        ) {
            return;
        }

        $templatePaths->setTemplateRootPaths(array_merge(
            $templatePaths->getTemplateRootPaths(),
            ['EXT:oauth2_client/Resources/Private/Templates/'],
            $extensionConfiguration['view']['templateRootPaths'] ?? []
        ));
        $templatePaths->setLayoutRootPaths(array_merge(
            $templatePaths->getLayoutRootPaths(),
            ['EXT:oauth2_client/Resources/Private/Layouts/'],
            $extensionConfiguration['view']['layoutRootPaths'] ?? []
        ));
        $templatePaths->setPartialRootPaths(array_merge(
            $templatePaths->getPartialRootPaths(),
            ['EXT:oauth2_client/Resources/Private/Partials/'],
            $extensionConfiguration['view']['partialRootPaths'] ?? []
        ));
    }
}
