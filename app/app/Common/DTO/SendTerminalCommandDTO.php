<?php

namespace App\Common\DTO;

use App\Models\Terminal;
use Illuminate\Http\Request;

readonly class SendTerminalCommandDTO
{
    public function __construct(
        public string $message,
        public int $userId,
        public int $terminalId
    ) {}

    public static function fromRequest(Request $request, Terminal $terminal): self
    {
        return new self(
            message: $request->input('message'),
            userId: $request->user()->id,
            terminalId: $terminal->id
        );
    }
}

