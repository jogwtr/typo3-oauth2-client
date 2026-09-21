<?php

declare(strict_types=1);

/*
 * This file is part of the OAuth2 Client extension for TYPO3
 * - (c) 2021 Waldhacker UG
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

namespace Waldhacker\Oauth2ClientTest\Backend\LoginProvider;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface;
use TYPO3\CMS\Core\View\ViewInterface;

class Oauth2LoginProvider implements LoginProviderInterface
{
    public const PROVIDER_ID = '1616569532';

    public function render($view, $pageRenderer, $loginController)
    {
        if (method_exists($view, 'setTemplatePathAndFilename')) {
            $view->setTemplatePathAndFilename('EXT:oauth2_client_test/Resources/Private/Templates/Backend/Oauth2LoginProvider.html');
        }
    }

    public function modifyView(ServerRequestInterface $request, ViewInterface $view): string
    {
        if (method_exists($view, 'getRenderingContext')) {
            $templatePaths = $view->getRenderingContext()->getTemplatePaths();
            if (method_exists($templatePaths, 'getTemplateRootPaths') && method_exists($templatePaths, 'setTemplateRootPaths')) {
                $templatePaths->setTemplateRootPaths(array_merge(
                    $templatePaths->getTemplateRootPaths(),
                    ['EXT:oauth2_client_test/Resources/Private/Templates/']
                ));
            }
        }

        return 'Backend/Oauth2LoginProvider';
    }
}
