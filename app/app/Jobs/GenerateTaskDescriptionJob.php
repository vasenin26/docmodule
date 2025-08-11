<?php

namespace App\Jobs;

use App\Common\DTO\DifferenceDataDTO;
use App\Interfaces\TaskDescriptionGeneratorInterface;
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
        public DifferenceDataDTO $differenceData
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
        if ($this->differenceData->isNewPage) {
            return 'New page created: ' . $this->differenceData->newVersionTitle;
        }

        $title = 'Page updated: ' . $this->differenceData->newVersionTitle;

        // Use diff_output if available for more detailed information
        if ($this->differenceData->diffOutput && !empty($this->differenceData->diffOutput)) {
            $diffLines = explode("\n", trim($this->differenceData->diffOutput));
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
        } elseif ($this->differenceData->titleChanged || $this->differenceData->contentChanged) {
            // Fallback to old logic for backward compatibility
            if ($this->differenceData->titleChanged && $this->differenceData->contentChanged) {
                $title .= ' (title and content changed)';
            } elseif ($this->differenceData->titleChanged) {
                $title .= ' (title changed)';
            } elseif ($this->differenceData->contentChanged) {
                $title .= ' (content changed)';
            }
        }

        return $title;
    }
}
