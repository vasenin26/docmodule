<?php

namespace App\Jobs;

use App\Interfaces\TaskTrackerInterface;
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
        public string $title,
        public string $description
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TaskTrackerInterface $taskTracker): void
    {
        $taskTracker->createTask($this->title, $this->description);
    }
}
