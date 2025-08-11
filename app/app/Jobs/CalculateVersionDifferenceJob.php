<?php

namespace App\Jobs;

use App\Common\DTO\DifferenceDataDTO;
use App\Interfaces\DiffGeneratorInterface;
use App\Models\Page;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateVersionDifferenceJob implements ShouldQueue
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
        public int $newVersionId,
        public ?int $oldVersionId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(DiffGeneratorInterface $diffGenerator): void
    {
        $newVersion = Page::findOrFail($this->newVersionId);
        $oldVersion = $this->oldVersionId ? Page::find($this->oldVersionId) : null;

        $differenceData = $this->calculateDifference($newVersion, $oldVersion, $diffGenerator);

        // Dispatch the next job in the chain
        GenerateTaskDescriptionJob::dispatch($differenceData);
    }

    /**
     * Calculate difference between two versions
     */
    private function calculateDifference(Page $newVersion, ?Page $oldVersion, DiffGeneratorInterface $diffGenerator): DifferenceDataDTO
    {
        $isNewPage = $oldVersion === null;
        $titleChanged = false;
        $contentChanged = false;
        $diffOutput = '';

        if ($oldVersion) {
            $titleChanged = $newVersion->title !== $oldVersion->title;
            $contentChanged = $newVersion->content !== $oldVersion->content;

            // Generate diff output in git diff format
            $diffOutput = $diffGenerator->generateDiff($oldVersion->content, $newVersion->content);
            if ($titleChanged) {
                $diffOutput = $diffGenerator->generateDiff($oldVersion->title, $newVersion->title, 'title') . "\n" . $diffOutput;
            }
        } else {
            // For new pages, show all content as added
            $diffOutput = $diffGenerator->generateDiff('', $newVersion->content);
            if (!empty($newVersion->title)) {
                $diffOutput = $diffGenerator->generateDiff('', $newVersion->title, 'title') . "\n" . $diffOutput;
            }
        }

        return new DifferenceDataDTO(
            diffOutput: $diffOutput,
            newVersionTitle: $newVersion->title,
            isNewPage: $isNewPage,
            titleChanged: $titleChanged,
            contentChanged: $contentChanged
        );
    }
}
