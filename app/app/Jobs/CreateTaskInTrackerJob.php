<?php

namespace App\Jobs;

use App\Interfaces\TaskTrackerInterface;
use App\Models\VersionDiffTask;
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
        public int $versionDiffTaskId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TaskTrackerInterface $taskTracker): void
    {
        $versionDiffTask = VersionDiffTask::with(['pageVersion.page'])->findOrFail($this->versionDiffTaskId);
        $page = $versionDiffTask->pageVersion->page;

        // Генерируем заголовок задачи на основе информации о версии страницы
        $title = $this->generateTaskTitle($versionDiffTask->pageVersion);

        // Получаем описание из VersionDiffTask
        $description = $versionDiffTask->content;

        // Создаем задачу в трекере
        $taskTracker->createTask($title, $description);
    }

    /**
     * Генерация заголовка задачи на основе информации о версии страницы
     */
    private function generateTaskTitle($currentVersion): string
    {
        $previousVersion = $currentVersion->previousVersion;
        $page = $currentVersion->page;

        if (!$previousVersion) {
            return 'New page created: ' . $currentVersion->title;
        }

        $title = 'Page updated: ' . $currentVersion->title;

        // Определяем, что именно изменилось
        $titleChanged = $currentVersion->title !== $previousVersion->title;
        $contentChanged = $currentVersion->content !== $previousVersion->content;

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
