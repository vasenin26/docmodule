<?php

namespace App\Common\DTO;

use App\Http\Requests\Api\Techplane\MarkDoneRequest;
use App\Models\Techplane;

class TechplaneMarkDoneDTO
{
    public function __construct(
        public int $techplaneId,
        public string $mergeRequestUrl,
        public int $userId,
    ) {}

    public static function fromRequest(MarkDoneRequest $request, Techplane $techplane): self
    {
        return new self(
            techplaneId: $techplane->id,
            mergeRequestUrl: (string) $request->validated('mergeRequestUrl'),
            userId: (int) $request->user()->id,
        );
    }
}


