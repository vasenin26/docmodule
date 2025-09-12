<?php

namespace App\Common\DTO;

use App\Http\Requests\SendImplementationMessageRequest;
use App\Models\Implementation;

readonly class SendImplementationMessageDTO
{
    public function __construct(
        public int $implementationId,
        public string $message,
        public int $userId
    ) {}

    public static function fromRequest(SendImplementationMessageRequest $request, Implementation $implementation): self
    {
        return new self(
            implementationId: $implementation->id,
            message: $request->validated()['message'],
            userId: $request->user()->id
        );
    }
}
