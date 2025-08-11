<?php

namespace App\Common\Stub;

use App\Interfaces\KeyProviderInterface;

class KeyProvider implements KeyProviderInterface
{

    public function getKey(): ?string
    {
        return null;
    }
}
