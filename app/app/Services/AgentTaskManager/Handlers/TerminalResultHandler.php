<?php

namespace App\Services\AgentTaskManager\Handlers;

use App\Interfaces\LLM\AgentResultHandlerInterface;
use App\Models\AgentTask;
use App\Models\Terminal;
use Exception;

class TerminalResultHandler implements AgentResultHandlerInterface
{
    const OPTION_TERMINAL_ID = 'terminal_id';

    public function __construct(private Terminal $terminal)
    {
    }

    public function getOptions(): array
    {
        return [
            self::OPTION_TERMINAL_ID => $this->terminal->id,
        ];
    }

    public function handleResult(?string $result): void
    {
        // ВАЖНО: Обработчику не нужно обновлять чат терминала,
        // так как агент сам добавит ответ в чат
        // Метод оставлен пустым или может содержать логирование
    }

    public static function createFromTask(AgentTask $task): static
    {
        $terminalId = $task->handler_options[self::OPTION_TERMINAL_ID] ?? null;

        if (is_null($terminalId)) {
            throw new Exception('AgentTask have no required option', 500);
        }

        $terminal = Terminal::findOrFail($terminalId);

        return new static($terminal);
    }

    public function getTargetResource(): ?\App\Interfaces\DisplayableResource
    {
        return null;
    }
}

