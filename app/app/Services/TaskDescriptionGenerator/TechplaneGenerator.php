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
     * Generate techplan based on task description and attached files
     *
     * @param string $taskDescription Task description for which to generate techplan
     * @param array $attachedFiles List of attached files from the page
     * @return LLMGenerationResult Generated techplan
     */
    public function generate(string $taskDescription, array $attachedFiles = []): LLMGenerationResult
    {
        try {
            $prompt = $this->buildPrompt($taskDescription, $attachedFiles);
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
                'attached_files_count' => count($attachedFiles),
            ]);

            // Возвращаем fallback техплан в случае ошибки
            return $this->generateFallbackTechplan($taskDescription, $attachedFiles);
        }
    }

    /**
     * Build the prompt for the AI model
     */
    private function buildPrompt(string $taskDescription, array $attachedFiles = []): string
    {
        $prompt = "Создай детальный технический план для следующей задачи:\n\n";
        $prompt .= "=== ОПИСАНИЕ ЗАДАЧИ ===\n";
        $prompt .= $taskDescription . "\n\n";
        
        if (!empty($attachedFiles)) {
            $prompt .= "=== ПРИКРЕПЛЁННЫЕ ФАЙЛЫ К СТРАНИЦЕ ===\n";
            $prompt .= "При создании техплана учти следующие файлы, прикреплённые к странице документации:\n\n";
            foreach ($attachedFiles as $index => $fileUrl) {
                $prompt .= ($index + 1) . ". " . $fileUrl . "\n";
            }
            $prompt .= "\nИспользуй эти файлы как дополнительный контекст для создания более точного техплана. ";
            $prompt .= "Анализируй их содержимое для понимания текущей архитектуры и структуры проекта.\n\n";
        }
        
        $prompt .= "=== ТРЕБОВАНИЯ К ТЕХПЛАНУ ===\n";
        $prompt .= "Технический план должен включать:\n";
        $prompt .= "- Пошаговое руководство по реализации\n";
        $prompt .= "- Конкретные пути к файлам, которые нужно изменить\n";
        $prompt .= "- Примеры кода для ключевых изменений\n";
        $prompt .= "- Технические детали и особенности реализации\n";
        $prompt .= "- Последовательность выполнения задач\n";
        if (!empty($attachedFiles)) {
            $prompt .= "- Анализ прикреплённых файлов и их влияние на реализацию\n";
        }
        $prompt .= "\nТехплан должен быть достаточно подробным, чтобы разработчик мог его выполнить пошагово.";
        
        return $prompt;
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
            "4. **Технические детали** - опиши особенности реализации\n" .
            (!empty($this->repositories) ? "5. **Анализ прикреплённых файлов** - изучи файлы для понимания контекста\n" : "") . "\n" .

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
    private function generateFallbackTechplan(string $taskDescription, array $attachedFiles = []): LLMGenerationResult
    {
        $techplan = "# Технический план\n\n";
        $techplan .= "## Описание задачи\n\n";
        $techplan .= $taskDescription . "\n\n";
        
        if (!empty($attachedFiles)) {
            $techplan .= "## Прикреплённые файлы\n\n";
            foreach ($attachedFiles as $index => $fileUrl) {
                $techplan .= ($index + 1) . ". " . $fileUrl . "\n";
            }
            $techplan .= "\n";
        }
        
        $techplan .= "## План работ\n\n";
        $techplan .= "1. Проанализировать требования\n";
        $techplan .= "2. Определить файлы для изменения\n";
        if (!empty($attachedFiles)) {
            $techplan .= "3. Изучить прикреплённые файлы\n";
            $techplan .= "4. Реализовать изменения с учётом существующей архитектуры\n";
            $techplan .= "5. Протестировать функциональность\n\n";
        } else {
            $techplan .= "3. Реализовать изменения\n";
            $techplan .= "4. Протестировать функциональность\n\n";
        }
        $techplan .= "_Техплан сгенерирован автоматически из-за недоступности LLM сервиса._";

        return new LLMGenerationResult($techplan, null);
    }
}
