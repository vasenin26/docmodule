<?php

namespace App\Services\TaskDescriptionGenerator;

use App\Common\DTO\LLMGenerationResult;
use App\Interfaces\ContentGenerator\TechplaneGeneratorInterface;
use App\Interfaces\LLM\ContentGenerator;
use App\Models\LLMChat;
use App\Models\Repository;
use Illuminate\Support\Facades\Log;

class TechplaneGenerator implements TechplaneGeneratorInterface
{
    /**
     * @param ContentGenerator $llmGenerator
     * @param array<Repository> $repositories
     */
    public function __construct(
        private ContentGenerator $llmGenerator,
        private array            $repositories = [],
    )
    {
    }

    /**
     * Generate techplan based on task description
     *
     * @param string $taskDescription Task description for which to generate techplan
     * @return LLMGenerationResult Generated techplan
     */
    public function generate(string $taskDescription): LLMGenerationResult
    {
        try {
            $prompt = $this->buildPrompt($taskDescription);
            $llmResult = $this->llmGenerator->generate($prompt, $this->getSystemPrompt());

            $chat = LLMChat::create([
                'messages' => $llmResult->messages,
                'prompt_tokens' => $llmResult->prompt_tokens,
                'completion_tokens' => $llmResult->completion_tokens,
                'total_tokens' => $llmResult->total_tokens
            ]);

            return new LLMGenerationResult($llmResult->answer, $chat->id);
        } catch (\Exception $e) {
            Log::error('Failed to generate techplane with LLM', [
                'error' => $e->getMessage(),
                'task_description' => $taskDescription,
            ]);

            // Возвращаем fallback техплан в случае ошибки
            return $this->generateFallbackTechplan($taskDescription);
        }
    }

    /**
     * Build the prompt for the AI model
     */
    private function buildPrompt(string $taskDescription): string
    {
        return "Создай детальный технический план для следующей задачи:\n\n" .
            $taskDescription .
            "\n\nТехнический план должен включать:\n" .
            "- Пошаговое руководство по реализации\n" .
            "- Конкретные пути к файлам, которые нужно изменить\n" .
            "- Примеры кода для ключевых изменений\n" .
            "- Технические детали и особенности реализации\n" .
            "- Последовательность выполнения задач\n\n" .
            "Техплан должен быть достаточно подробным, чтобы разработчик мог его выполнить пошагово.";
    }

    /**
     * Get the system prompt for the AI model
     */
    private function getSystemPrompt(): string
    {
        $prompt = "Ты - технический архитектор, который создает детальные технические планы для разработчиков. " .
            "Твоя задача - на основе описания задачи создать технический план, который включает:\n\n" .
            
            "1. **Пошаговое руководство** - четкая последовательность действий\n" .
            "2. **Конкретные пути к файлам** - укажи точные файлы, которые нужно изменить\n" .
            "3. **Примеры кода** - покажи как должен выглядеть код\n" .
            "4. **Технические детали** - опиши особенности реализации\n\n" .
            
            "Техплан должен быть:\n" .
            "- Детальным и пошаговым\n" .
            "- Понятным для разработчиков\n" .
            "- На русском языке\n" .
            "- В формате Markdown с четкой структурой\n" .
            "- Содержать конкретные пути к файлам\n" .
            "- Включать примеры кода где это необходимо\n\n";

        if (!empty($this->repositories)) {
            $prompt .= "Проект включает следующие репозитории:\n";
            
            foreach ($this->repositories as $repository) {
                $prompt .= '- ' . $repository->url . "\n";
            }
            
            $prompt .= "\nИсследуй репозиторий чтобы получить дополнительную " .
                "информацию о структуре проекта и создать более точный техплан.\n\n";
        }

        $prompt .= "Формат ответа: детальный технический план в формате Markdown.";

        return $prompt;
    }

    /**
     * Generate fallback techplan when LLM API fails
     */
    private function generateFallbackTechplan(string $taskDescription): LLMGenerationResult
    {
        $techplan = "# Технический план\n\n";
        $techplan .= "## Описание задачи\n\n";
        $techplan .= $taskDescription . "\n\n";
        $techplan .= "## План работ\n\n";
        $techplan .= "1. Проанализировать требования\n";
        $techplan .= "2. Определить файлы для изменения\n";
        $techplan .= "3. Реализовать изменения\n";
        $techplan .= "4. Протестировать функциональность\n\n";
        $techplan .= "_Техплан сгенерирован автоматически из-за недоступности LLM сервиса._";

        return new LLMGenerationResult($techplan, null);
    }
}
