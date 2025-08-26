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
            $prompt = $this->buildPrompt($differenceData);
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

        $changes[] = "Новая версия: ";
        $changes[] = "--------- \n";
        $changes[] = $differenceData->newVersionContent . "\n";
        $changes[] = "--------- \n";

        if (!empty($this->repositories)) {
            $changes[] = "Проект включает следующие репозитории: \n";

            foreach ($this->repositories as $repository) {
                $changes[] = '- ' . $repository->url . "\n";
            }

            $changes[] = "\n Исследуй репозиторий чтобы получить дополнительную " .
                "информацию о продукте и создать лучшее описание задачи. \n\n";
        }

        return "Создай описание задачи на основе следующих изменений в документации:\n\n" .
            implode("\n\n", $changes) .
            "\n\nОписание должно быть понятным для разработчиков и содержать основную суть изменений." .
            "\n\nСохрани описание в хранилище.";
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
