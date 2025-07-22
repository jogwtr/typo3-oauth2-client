<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Provider;

use Psr\Http\Message\UriInterface as Uri;

interface CallbackUri
{
    public function build(): Uri;
}
