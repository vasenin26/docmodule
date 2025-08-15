<?php

namespace App\Services\TaskDescriptionGenerator;

use App\Common\DTO\LLMGenerationResult;
use App\Interfaces\ContentGenerator\ActualizationGeneratorInterface;
use App\Interfaces\LLM\ContentGenerator;
use App\Models\LLMChat;
use App\Services\RepositoryService\RepositoryProvider;
use Illuminate\Support\Facades\Log;

class ActualizationGenerator implements ActualizationGeneratorInterface
{
    /**
     * @param ContentGenerator $contentGenerator
     * @param RepositoryProvider $repositoryProvider
     * @param array $repositories
     */
    public function __construct(
        private ContentGenerator $contentGenerator,
        private RepositoryProvider $repositoryProvider,
        private array $repositories = [],
    ) {
    }

    /**
     * Актуализировать содержимое страницы на основе прикрепленных файлов
     *
     * @param string $currentContent Текущее содержимое страницы
     * @param array $attachedFiles Список прикрепленных файлов
     * @return LLMGenerationResult Результат генерации
     */
    public function actualize(string $currentContent, array $attachedFiles): LLMGenerationResult
    {
        try {
            $prompt = $this->buildPrompt($currentContent, $attachedFiles);
            $llmResult = $this->contentGenerator->generate($prompt, $this->getSystemPrompt());

            $chat = LLMChat::create([
                'messages' => $llmResult->messages,
                'prompt_tokens' => $llmResult->prompt_tokens,
                'completion_tokens' => $llmResult->completion_tokens,
                'total_tokens' => $llmResult->total_tokens
            ]);

            return new LLMGenerationResult($llmResult->answer, $chat->id);
        } catch (\Exception $e) {
            Log::error('Failed to actualize page content', [
                'error' => $e->getMessage(),
                'current_content_length' => strlen($currentContent),
                'attached_files_count' => count($attachedFiles),
            ]);

            // Возвращаем fallback описание в случае ошибки
            return $this->generateFallbackContent($currentContent, $attachedFiles);
        }
    }

    /**
     * Построить промпт для актуализации
     */
    private function buildPrompt(string $currentContent, array $attachedFiles): string
    {
        $prompt = "Проанализируй текущее содержимое страницы документации и обнови его на основе анализа прикрепленных файлов кода.\n\n";
        
        $prompt .= "ТЕКУЩЕЕ СОДЕРЖИМОЕ СТРАНИЦЫ:\n";
        $prompt .= "=================================\n";
        $prompt .= $currentContent . "\n";
        $prompt .= "=================================\n\n";

        if (!empty($attachedFiles)) {
            $prompt .= "ПРИКРЕПЛЕННЫЕ ФАЙЛЫ ДЛЯ АНАЛИЗА:\n";
            $prompt .= "==============================\n";
            foreach ($attachedFiles as $index => $fileUrl) {
                $prompt .= ($index + 1) . ". " . $fileUrl . "\n";
            }
            $prompt .= "==============================\n\n";
        }

        $prompt .= "ЗАДАЧА:\n";
        $prompt .= "Изучи прикрепленные файлы кода и обнови документацию так, чтобы она соответствовала текущему состоянию кода. ";
        $prompt .= "Выяви расхождения между документацией и реальным кодом и внеси необходимые исправления. ";
        $prompt .= "Если найдены расхождения, добавь в конец документации раздел с описанием обнаруженных проблем.\n\n";
        
        $prompt .= "ТРЕБОВАНИЯ К РЕЗУЛЬТАТУ:\n";
        $prompt .= "- Сохрани структуру и стиль исходной документации\n";
        $prompt .= "- Обнови информацию, которая не соответствует коду\n";
        $prompt .= "- Добавь новые сведения, если они важны для понимания\n";
        $prompt .= "- Укажи найденные расхождения в отдельном разделе\n";
        $prompt .= "- Используй тот же язык, что и в исходной документации\n";
        $prompt .= "- Результат должен быть в формате Markdown\n";

        return $prompt;
    }

    /**
     * Получить системный промпт для LLM
     */
    private function getSystemPrompt(): string
    {
        $prompt = "Ты - опытный технический писатель, который специализируется на актуализации документации программных проектов. ";
        $prompt .= "Твоя задача - проанализировать код и обновить документацию так, чтобы она точно отражала текущее состояние системы.\n\n";
        
        $prompt .= "ВАЖНЫЕ ПРИНЦИПЫ:\n";
        $prompt .= "- Документация является источником истины о том, как ДОЛЖНА работать система\n";
        $prompt .= "- Если код не соответствует документации, это означает, что код нужно исправить\n";
        $prompt .= "- Однако актуализация документации означает приведение её в соответствие с ТЕКУЩИМ состоянием кода\n";
        $prompt .= "- Обязательно отмечай найденные расхождения для последующего исправления кода\n\n";
        
        $prompt .= "CAPABILITIES:\n";
        $prompt .= "- Используй инструменты для чтения файлов из репозиториев\n";
        $prompt .= "- Анализируй структуру проекта и зависимости\n";
        $prompt .= "- Ищи файлы по имени или содержимому\n";
        $prompt .= "- Изучай конфигурационные файлы и настройки\n\n";

        if (!empty($this->repositories)) {
            $prompt .= "ДОСТУПНЫЕ РЕПОЗИТОРИИ:\n";
            foreach ($this->repositories as $repository) {
                $prompt .= "- " . $repository->url . "\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "Результат должен быть качественной, актуальной документацией в формате Markdown.\n";
        $prompt .= "Сохрани обновленную документацию в хранилище.";

        return $prompt;
    }

    /**
     * Сгенерировать fallback контент в случае ошибки LLM
     */
    private function generateFallbackContent(string $currentContent, array $attachedFiles): LLMGenerationResult
    {
        $content = $currentContent;
        
        // Добавляем информацию о попытке актуализации
        $content .= "\n\n---\n\n";
        $content .= "## Информация об актуализации\n\n";
        $content .= "**Статус:** Актуализация выполнена с ограничениями (резервный режим)\n\n";
        $content .= "**Дата:** " . now()->format('d.m.Y H:i') . "\n\n";
        
        if (!empty($attachedFiles)) {
            $content .= "**Проанализированные файлы:**\n";
            foreach ($attachedFiles as $file) {
                $content .= "- " . $file . "\n";
            }
            $content .= "\n";
        }
        
        $content .= "**Примечание:** Автоматическая актуализация была выполнена в упрощенном режиме. ";
        $content .= "Рекомендуется ручная проверка соответствия документации текущему состоянию кода.\n";

        return new LLMGenerationResult($content, null);
    }
}
