<?php

namespace App\Services\TaskDescriptionGenerator;

use App\Common\DTO\DifferenceDataDTO;
use App\Common\DTO\LLMGenerationResult;
use App\Interfaces\ContentGenerator\DiffDescriptionGeneratorInterface;
use App\Interfaces\LLM\ContentGenerator;
use App\Interfaces\LLM\PromptProviderInterface;
use App\Models\LLMChat;
use App\Models\Repository;
use Illuminate\Support\Facades\Log;

readonly class DiffDescriptionGenerator implements DiffDescriptionGeneratorInterface
{

    /**
     * @param ContentGenerator $llmGenerator
     * @param PromptProviderInterface $promptProvider
     * @param array<Repository> $repositories
     */
    public function __construct(
        private ContentGenerator        $llmGenerator,
        private PromptProviderInterface $promptProvider,
        private array                   $repositories = [],
    )
    {
    }

    public function generate(DifferenceDataDTO $differenceData): LLMGenerationResult
    {
        try {
            // Получаем прикрепленные файлы из страницы, если они есть
            $attachedFiles = [];
            if ($differenceData->newVersionId) {
                // Здесь можно добавить логику получения прикрепленных файлов
                // Пока оставляем пустым массивом
            }
            
            $prompt = $this->promptProvider->getDescriptionGeneratorInstructions($differenceData, $this->repositories, $attachedFiles);
            $llmResult = $this->llmGenerator->generate($prompt, $this->promptProvider->getDescriptionGeneratorRole());

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
