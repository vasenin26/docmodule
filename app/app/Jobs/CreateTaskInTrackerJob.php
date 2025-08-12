<?php

namespace App\Jobs;

use App\Interfaces\TaskTrackerInterface;
use App\Models\PageDiffDescription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateTaskInTrackerJob implements ShouldQueue
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
    public function handle(TaskTrackerInterface $taskTracker): void
    {
        $pageDiffDescription = PageDiffDescription::with(['page.previousVersion'])->findOrFail($this->pageDiffDescriptionId);
        $page = $pageDiffDescription->page;

        // Генерируем заголовок задачи на основе информации о странице
        $title = $this->generateTaskTitle($page);
        
        // Получаем описание из PageDiffDescription
        $description = $pageDiffDescription->content;

        // Создаем задачу в трекере
        $taskTracker->createTask($title, $description);
    }

    /**
     * Генерация заголовка задачи на основе информации о странице
     */
    private function generateTaskTitle($page): string
    {
        $previousVersion = $page->previousVersion;

        if (!$previousVersion) {
            return 'New page created: ' . $page->title;
        }

        $title = 'Page updated: ' . $page->title;

        // Определяем, что именно изменилось
        $titleChanged = $page->title !== $previousVersion->title;
        $contentChanged = $page->content !== $previousVersion->content;

        if ($titleChanged && $contentChanged) {
            $title .= ' (title and content changed)';
        } elseif ($titleChanged) {
            $title .= ' (title changed)';
        } elseif ($contentChanged) {
            $title .= ' (content changed)';
        }

        return $title;
    }
}
