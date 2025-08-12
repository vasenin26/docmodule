<?php

namespace App\Jobs;

use App\Common\DTO\DifferenceDataDTO;
use App\Interfaces\TaskDescriptionGeneratorInterface;
use App\Models\PageDiffDescription;
use App\Services\DiffGenerator\DiffGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
    public function handle(TaskDescriptionGeneratorInterface $descriptionGenerator, DiffGeneratorService $diffGenerator): void
    {
        $pageDiffDescription = PageDiffDescription::with(['page.previousVersion', 'page.creator'])->findOrFail($this->pageDiffDescriptionId);
        $page = $pageDiffDescription->page;

        // Создаем DifferenceDataDTO на основе информации о странице
        $differenceData = $this->createDifferenceDataDTO($page, $diffGenerator);

        // Генерируем описание задачи
        $description = $descriptionGenerator->generateDescription($differenceData);

        // Сохраняем сгенерированное описание в базе данных
        $pageDiffDescription->update([
            'content' => $description
        ]);

        // Запускаем следующий job в цепочке
        CreateTaskInTrackerJob::dispatch($this->pageDiffDescriptionId);
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
                isNewPage: true,
                newVersionId: $page->id,
                newVersionTitle: $page->title,
                newVersionContent: $page->content,
                previousVersionId: null,
                previousVersionTitle: null,
                previousVersionContent: null,
                titleChanged: false,
                contentChanged: false,
                diffOutput: null
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
            isNewPage: false,
            newVersionId: $page->id,
            newVersionTitle: $page->title,
            newVersionContent: $page->content,
            previousVersionId: $previousVersion->id,
            previousVersionTitle: $previousVersion->title,
            previousVersionContent: $previousVersion->content,
            titleChanged: $titleChanged,
            contentChanged: $contentChanged,
            diffOutput: $diffOutput
        );
    }


}
