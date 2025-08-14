<?php

namespace App\Services\TaskDescriptionGenerator;

use App\Common\DTO\DifferenceDataDTO;
use App\Common\DTO\LLMGenerationResult;
use App\Interfaces\ContentGenerator\TaskDescriptionGeneratorInterface;
use App\Interfaces\LLM\LLMGenerator;
use App\Models\LLMChat;
use Illuminate\Support\Facades\Log;

class LLMDescriptionGenerator implements TaskDescriptionGeneratorInterface
{

    public function __construct(
        private LLMGenerator $llmGenerator
    )
    {
    }

    /**
     * Generate task description based on version difference data
     *
     * @param DifferenceDataDTO $differenceData Data about the difference between versions
     * @return string Generated task description
     */
    public function generateDescription(DifferenceDataDTO $differenceData): LLMGenerationResult
    {
        try {
            $prompt = $this->buildPrompt($differenceData);
            $llmResult = $this->llmGenerator->generate($prompt, $this->getSystemPrompt());

            $chat = LLMChat::create([
                'messages' => $llmResult->messages,
                'prompt_tokens' => $llmResult->prompt_tokens,
                'completion_tokens' => $llmResult->completion_tokens,
                'total_tokens' => $llmResult->total_tokens
            ]);

            return new LLMGenerationResult($llmResult->answer, $chat->id);
        } catch (\Exception $e) {
            Log::error('Failed to generate task description with OpenAI', [
                'error' => $e->getMessage(),
                'difference_data' => $differenceData->toArray(),
            ]);

            // Возвращаем fallback описание в случае ошибки
            return $this->generateFallbackDescription($differenceData);
        }
    }

    /**
     * Build the prompt for the AI model
     */
    private function buildPrompt(DifferenceDataDTO $differenceData): string
    {
        $changes = [];

        if ($differenceData->diffOutput && !empty($differenceData->diffOutput)) {
            $changes[] = "Изменения в формате git diff:\n" . $differenceData->diffOutput;
        } else {
            // Fallback to old format for backward compatibility
            if (!empty($differenceData->addedLines)) {
                $changes[] = "Добавлено строк: " . count($differenceData->addedLines);
                $changes[] = "Добавленный код:\n" . implode("\n", array_slice($differenceData->addedLines, 0, 10));
            }

            if (!empty($differenceData->removedLines)) {
                $changes[] = "Удалено строк: " . count($differenceData->removedLines);
                $changes[] = "Удаленный код:\n" . implode("\n", array_slice($differenceData->removedLines, 0, 10));
            }
        }

        if ($differenceData->newVersionTitle) {
            $changes[] = "Страница: " . $differenceData->newVersionTitle;
        }

        return "Создай описание задачи на основе следующих изменений в документации:\n\n" .
            implode("\n\n", $changes) .
            "\n\nОписание должно быть понятным для разработчиков и содержать основную суть изменений.";
    }

    /**
     * Get the system prompt for the AI model
     */
    private function getSystemPrompt(): string
    {
        return "Ты - опытный менеджер продукта, который создает краткие и информативные описания задач на основе изменений в документации. " .
            "Твоя задача сформировать задачу для разработчиков на основе изменений в документации. " .
            "На основе различия необходимо сформировать описание требуемых изменений необходимых для того, чтобы привести кодовую базу к состоянию удовлетворяющему новую версию документации." .
            "Описание должно быть:\n" .
            "- Информативным\n" .
            "- Понятным для  разработчиков\n" .
            "- На русском языке\n" .
            "- Без технических деталей, если они не критичны\n" .
            "- Задача должна быть в формате markdown\n" .
            "Сохрани описание в хранилище.";
    }

    /**
     * Generate fallback description when OpenAI API fails
     */
    private function generateFallbackDescription(DifferenceDataDTO $differenceData): LLMGenerationResult
    {
        $description = 'Задача создана на основе изменений в документации.';

        if ($differenceData->diffOutput && !empty($differenceData->diffOutput)) {
            $description .= "\n\nИзменения:\n" . $differenceData->diffOutput;
        }

        if ($differenceData->newVersionTitle) {
            $description .= "\n\nСтраница: " . $differenceData->newVersionTitle;
        }

        if ($differenceData->isNewPage) {
            $description .= "\nТип: Создана новая страница";
        } else {
            $description .= "\nТип: Страница обновлена";
        }

        return new LLMGenerationResult($description, null);
    }
}
