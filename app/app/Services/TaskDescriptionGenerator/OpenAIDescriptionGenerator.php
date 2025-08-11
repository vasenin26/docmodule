<?php

namespace App\Services\TaskDescriptionGenerator;

use OpenAI\OpenAI;
use Illuminate\Support\Facades\Log;
use App\Interfaces\LLMGenerator;

class OpenAIDescriptionGenerator implements TaskDescriptionGeneratorInterface
{

    public function __construct(
        private LLMGenerator $llmGenerator
    )
    {
    }

    /**
     * Generate task description based on version difference data
     *
     * @param array $differenceData Data about the difference between versions
     * @return string Generated task description
     */
    public function generateDescription(array $differenceData): string
    {
        try {
            $prompt = $this->buildPrompt($differenceData);

            $description = $this->llmGenerator->generate($prompt, $this->getSystemPrompt());

            return trim($description);

        } catch (\Exception $e) {
            Log::error('Failed to generate task description with OpenAI', [
                'error' => $e->getMessage(),
                'difference_data' => $differenceData,
            ]);

            // Возвращаем fallback описание в случае ошибки
            return $this->generateFallbackDescription($differenceData);
        }
    }

    /**
     * Build the prompt for the AI model
     */
    private function buildPrompt(array $differenceData): string
    {
        $changes = [];
        
        // Use diff_output if available (new format)
        if (isset($differenceData['diff_output']) && !empty($differenceData['diff_output'])) {
            $changes[] = "Изменения в формате git diff:\n" . $differenceData['diff_output'];
        } else {
            // Fallback to old format for backward compatibility
            if (isset($differenceData['added_lines'])) {
                $changes[] = "Добавлено строк: " . count($differenceData['added_lines']);
                if (!empty($differenceData['added_lines'])) {
                    $changes[] = "Добавленный код:\n" . implode("\n", array_slice($differenceData['added_lines'], 0, 10));
                }
            }

            if (isset($differenceData['removed_lines'])) {
                $changes[] = "Удалено строк: " . count($differenceData['removed_lines']);
                if (!empty($differenceData['removed_lines'])) {
                    $changes[] = "Удаленный код:\n" . implode("\n", array_slice($differenceData['removed_lines'], 0, 10));
                }
            }
        }

        // Add page information
        if (isset($differenceData['new_version_title'])) {
            $changes[] = "Страница: " . $differenceData['new_version_title'];
        }

        if (isset($differenceData['is_new_page']) && $differenceData['is_new_page']) {
            $changes[] = "Тип: Создание новой страницы";
        } else {
            $changes[] = "Тип: Обновление существующей страницы";
        }

        if (isset($differenceData['modified_files'])) {
            $changes[] = "Измененные файлы: " . implode(", ", $differenceData['modified_files']);
        }

        if (isset($differenceData['commit_message'])) {
            $changes[] = "Сообщение коммита: " . $differenceData['commit_message'];
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
               "Отвечай только описанием задачи, без дополнительных комментариев.";
    }

    /**
     * Generate fallback description when OpenAI API fails
     */
    private function generateFallbackDescription(array $differenceData): string
    {
        $description = "Задача создана из разницы версий";

        if (isset($differenceData['commit_message'])) {
            $description .= ": " . $differenceData['commit_message'];
        }

        if (isset($differenceData['modified_files'])) {
            $description .= " (файлы: " . implode(", ", $differenceData['modified_files']) . ")";
        }

        return $description;
    }
}
