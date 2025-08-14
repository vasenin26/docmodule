<?php

namespace App\Jobs;

use App\Common\DTO\DifferenceDataDTO;
use App\Interfaces\AgentFactoryInterface;
use App\Interfaces\TaskDescriptionGeneratorInterface;
use App\Models\PageDiffDescription;
use App\Services\DiffGenerator\DiffGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class GenerateTaskDescriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $pageDiffDescriptionId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AgentFactoryInterface $agentFactory, DiffGeneratorService $diffGenerator): void
    {
        $pageDiffDescription = PageDiffDescription::with(['page.previousVersion', 'page.creator'])->findOrFail($this->pageDiffDescriptionId);

        try {
            // Устанавливаем статус "generating"
            $pageDiffDescription->update([
                'generation_status' => PageDiffDescription::STATUS_GENERATING
            ]);

            $page = $pageDiffDescription->page;

            // Создаем DifferenceDataDTO на основе информации о странице
            $differenceData = $this->createDifferenceDataDTO($page, $diffGenerator);

            // Генерируем описание задачи
            $descriptionGenerator = $agentFactory->getDescriptionGenerator($page->projectId);
            $generationResult = $descriptionGenerator->generateDescription($differenceData);

            // Сохраняем сгенерированное описание и обновляем статус
            $pageDiffDescription->update([
                'content' => $generationResult->result,
                'generation_status' => PageDiffDescription::STATUS_COMPLETED,
                'llm_chat_id' => $generationResult->chatId
            ]);

            // Запускаем следующий job в цепочке
            CreateTaskInTrackerJob::dispatch($this->pageDiffDescriptionId);
        } catch (Exception $e) {
            // Логируем ошибку
            Log::error('Failed to generate task description', [
                'page_diff_description_id' => $this->pageDiffDescriptionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Устанавливаем статус "failed"
            $pageDiffDescription->update([
                'generation_status' => PageDiffDescription::STATUS_FAILED
            ]);

            // Перебрасываем исключение для обработки системой очередей
            throw $e;
        }
    }

    /**
     * Создание DifferenceDataDTO на основе информации о странице
     */
    private function createDifferenceDataDTO($page, DiffGeneratorService $diffGenerator): DifferenceDataDTO
    {
        $previousVersion = $page->previousVersion;

        if (!$previousVersion) {
            // Новая страница
            return new DifferenceDataDTO(
                diffOutput: null,
                newVersionTitle: $page->title,
                isNewPage: true,
                titleChanged: false,
                contentChanged: false,
                newVersionId: $page->id,
                newVersionContent: $page->content,
                previousVersionId: null,
                previousVersionTitle: null,
                previousVersionContent: null
            );
        }

        // Обновленная страница
        $titleChanged = $page->title !== $previousVersion->title;
        $contentChanged = $page->content !== $previousVersion->content;

        // Генерируем diff если есть изменения
        $diffOutput = null;
        if ($titleChanged || $contentChanged) {
            $diffOutput = $diffGenerator->generateDiff($previousVersion->content, $page->content);
        }

        return new DifferenceDataDTO(
            diffOutput: $diffOutput,
            newVersionTitle: $page->title,
            isNewPage: false,
            titleChanged: $titleChanged,
            contentChanged: $contentChanged,
            newVersionId: $page->id,
            newVersionContent: $page->content,
            previousVersionId: $previousVersion->id,
            previousVersionTitle: $previousVersion->title,
            previousVersionContent: $previousVersion->content
        );
    }


}
