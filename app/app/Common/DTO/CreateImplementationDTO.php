<?php

namespace App\Common\DTO;

readonly class CreateImplementationDTO
{
    public function __construct(
        public int $techplaneId,
        public int $userId
    ) {}
}
