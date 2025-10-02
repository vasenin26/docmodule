<?php

namespace App\Common\DTO\Actualization;

use App\Models\Actualization;
use Illuminate\Http\Request;

readonly class SendActualizationMessageDTO
{
    public function __construct(
        public string $message,
        public int $userId,
        public int $actualizationId
    ) {}

    public static function fromRequest(Request $request, Actualization $actualization): self
    {
        return new self(
            message: $request->validated()['message'],
            userId: $request->user()->id,
            actualizationId: $actualization->id
        );
    }
}


