<?php

namespace App\Common\DTO;

use App\Http\Requests\SendTechplaneMessageRequest;
use App\Models\Techplane;

class SendTechplaneMessageDTO
{
    public function __construct(
        public readonly int $techplaneId,
        public readonly string $message,
        public readonly int $userId
    ) {}

    public static function fromRequest(SendTechplaneMessageRequest $request, Techplane $techplane): self
    {
        return new self(
            techplaneId: $techplane->id,
            message: $request->validated()['message'],
            userId: $request->user()->id
        );
    }
}
