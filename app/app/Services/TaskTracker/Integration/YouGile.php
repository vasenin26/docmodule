<?php

namespace App\Services\TaskTracker\Integration;

use App\Interfaces\TaskTrackerInterface;

class YouGile implements TaskTrackerInterface
{

    public function createTask(string $title, string $description): bool
    {
        // TODO: Implement createTask() method.
    }
}
