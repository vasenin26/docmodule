<?php

namespace App\Jobs;

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
    public function handle(): void
    {
        $newVersion = Page::findOrFail($this->newVersionId);
        $oldVersion = $this->oldVersionId ? Page::find($this->oldVersionId) : null;

        $differenceData = $this->calculateDifference($newVersion, $oldVersion);

        // Dispatch the next job in the chain
        GenerateTaskDescriptionJob::dispatch($differenceData);
    }

    /**
     * Calculate difference between two versions
     */
    private function calculateDifference(Page $newVersion, ?Page $oldVersion): array
    {
        $difference = [
            'new_version_id' => $newVersion->id,
            'new_version_title' => $newVersion->title,
            'new_version_content' => $newVersion->content,
            'is_new_page' => $oldVersion === null,
        ];

        if ($oldVersion) {
            $difference['old_version_id'] = $oldVersion->id;
            $difference['old_version_title'] = $oldVersion->title;
            $difference['old_version_content'] = $oldVersion->content;
            $difference['title_changed'] = $newVersion->title !== $oldVersion->title;
            $difference['content_changed'] = $newVersion->content !== $oldVersion->content;
        }

        return $difference;
    }
}
