<?php

namespace App\Jobs;

use App\Services\TaskDescriptionGenerator\TaskDescriptionGeneratorInterface;
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
        public array $differenceData
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TaskDescriptionGeneratorInterface $descriptionGenerator): void
    {
        $description = $descriptionGenerator->generateDescription($this->differenceData);

        // Generate task title based on difference data
        $title = $this->generateTaskTitle();

        // Dispatch the next job in the chain
        CreateTaskInTrackerJob::dispatch($title, $description);
    }

    /**
     * Generate task title based on difference data
     */
    private function generateTaskTitle(): string
    {
        if ($this->differenceData['is_new_page']) {
            return 'New page created: ' . $this->differenceData['new_version_title'];
        }

        $title = 'Page updated: ' . $this->differenceData['new_version_title'];
        
        if ($this->differenceData['title_changed'] && $this->differenceData['content_changed']) {
            $title .= ' (title and content changed)';
        } elseif ($this->differenceData['title_changed']) {
            $title .= ' (title changed)';
        } elseif ($this->differenceData['content_changed']) {
            $title .= ' (content changed)';
        }

        return $title;
    }
}
