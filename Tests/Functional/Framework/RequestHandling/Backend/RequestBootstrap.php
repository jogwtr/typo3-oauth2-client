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

namespace Waldhacker\Oauth2Client\Tests\Functional\Framework\RequestHandling\Backend;

use TYPO3\CMS\Backend\Http\Application;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use Waldhacker\Oauth2Client\Tests\Functional\Framework\RequestHandling\AbstractRequestBootstrap;

class RequestBootstrap extends AbstractRequestBootstrap
{
    protected const ENTRY_LEVEL = 1;
    protected const REQUEST_TYPE = SystemEnvironmentBuilder::REQUESTTYPE_BE;
    protected const TYPO3_CONTEXT = 'Testing/Backend';
    protected const SCRIPT = '/typo3/index.php';
    protected const APPLICATION = Application::class;
}
