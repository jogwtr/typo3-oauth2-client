<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Event;

use TYPO3\CMS\Core\Http\RedirectResponse;

final readonly class RedirectResponseCreated
{
    public function __construct(
        public RedirectResponse $redirectResponse,
    ) {}
}
