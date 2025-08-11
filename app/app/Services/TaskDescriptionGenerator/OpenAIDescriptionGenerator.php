<?php

namespace App\Services\TaskDescriptionGenerator;

use OpenAI\OpenAI;
use Illuminate\Support\Facades\Log;

class OpenAIDescriptionGenerator implements TaskDescriptionGeneratorInterface
{
    private OpenAI $client;
    private string $model;

    public function __construct()
    {
        $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');
        
        if (!$apiKey) {
            throw new \InvalidArgumentException('OpenAI API key is not configured');
        }

        $this->client = OpenAI::client($apiKey);
        $this->model = config('services.openai.model', 'gpt-4o-mini');
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

            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->getSystemPrompt()
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 500,
                'temperature' => 0.3,
            ]);

            $description = $response->choices[0]->message->content;
            
            // Логируем использование токенов для мониторинга расходов
            Log::info('OpenAI API usage', [
                'prompt_tokens' => $response->usage->promptTokens,
                'completion_tokens' => $response->usage->completionTokens,
                'total_tokens' => $response->usage->totalTokens,
                'model' => $this->model,
            ]);

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

        if (isset($differenceData['modified_files'])) {
            $changes[] = "Измененные файлы: " . implode(", ", $differenceData['modified_files']);
        }

        if (isset($differenceData['commit_message'])) {
            $changes[] = "Сообщение коммита: " . $differenceData['commit_message'];
        }

        return "Создай краткое и информативное описание задачи на основе следующих изменений в коде:\n\n" . 
               implode("\n\n", $changes) . 
               "\n\nОписание должно быть понятным для разработчиков и содержать основную суть изменений.";
    }

    /**
     * Get the system prompt for the AI model
     */
    private function getSystemPrompt(): string
    {
        return "Ты - опытный разработчик, который создает краткие и информативные описания задач на основе изменений в документации. " .
               "Твоя задача - проанализировать diff между версиями документации и создать понятное описание того, что было изменено. " .
               "Описание должно быть:\n" .
               "- Кратким (1-3 предложения)\n" .
               "- Информативным\n" .
               "- Понятным для других разработчиков\n" .
               "- На русском языке\n" .
               "- Без технических деталей, если они не критичны\n" .
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
