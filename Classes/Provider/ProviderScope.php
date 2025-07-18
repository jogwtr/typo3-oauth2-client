<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Provider;

enum ProviderScope: int
{
    case FE = 0b01;
    case BE = 0b10;
    case BOTH = 0b11;

    public function contains(ProviderScope $scope): bool
    {
        return ($this->value & $scope->value) === $scope->value;
    }
}
