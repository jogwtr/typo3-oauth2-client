<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Provider;

use Psr\Http\Message\UriInterface as Uri;
use TYPO3\CMS\Backend\Routing\UriBuilder;

interface CallbackUri
{
    public function build(UriBuilder $uriBuilder): Uri;
}
