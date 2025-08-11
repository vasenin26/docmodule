<?php

namespace App\Interfaces;

interface KeyProviderInterface
{
    public function getKey(): ?string;
}
