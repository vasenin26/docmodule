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
        
        // Use diff_output if available for more detailed information
        if (isset($this->differenceData['diff_output']) && !empty($this->differenceData['diff_output'])) {
            $diffLines = explode("\n", trim($this->differenceData['diff_output']));
            $addedLines = 0;
            $removedLines = 0;
            
            foreach ($diffLines as $line) {
                if (str_starts_with($line, '+')) {
                    $addedLines++;
                } elseif (str_starts_with($line, '-')) {
                    $removedLines++;
                }
            }
            
            $changes = [];
            if ($addedLines > 0) {
                $changes[] = "{$addedLines} line(s) added";
            }
            if ($removedLines > 0) {
                $changes[] = "{$removedLines} line(s) removed";
            }
            
            if (!empty($changes)) {
                $title .= ' (' . implode(', ', $changes) . ')';
            }
        } elseif (isset($this->differenceData['title_changed']) || isset($this->differenceData['content_changed'])) {
            // Fallback to old logic for backward compatibility
            if ($this->differenceData['title_changed'] && $this->differenceData['content_changed']) {
                $title .= ' (title and content changed)';
            } elseif ($this->differenceData['title_changed']) {
                $title .= ' (title changed)';
            } elseif ($this->differenceData['content_changed']) {
                $title .= ' (content changed)';
            }
        }

        return $title;
    }
}
