<?php

namespace Tests\Unit\Models;

use App\Models\VersionDiffTask;
use Tests\TestCase;

class VersionDiffTaskTest extends TestCase
{
    public function test_is_generating_returns_true_when_status_is_generating()
    {
        $task = new VersionDiffTask([
            'generation_status' => VersionDiffTask::STATUS_GENERATING
        ]);

        $this->assertTrue($task->isGenerating());
    }

    public function test_is_generating_returns_false_when_status_is_not_generating()
    {
        $statuses = [
            VersionDiffTask::STATUS_PENDING,
            VersionDiffTask::STATUS_COMPLETED,
            VersionDiffTask::STATUS_FAILED
        ];

        foreach ($statuses as $status) {
            $task = new VersionDiffTask([
                'generation_status' => $status
            ]);

            $this->assertFalse($task->isGenerating(), "isGenerating() should return false for status: $status");
        }
    }

    public function test_is_completed_returns_true_when_status_is_completed()
    {
        $task = new VersionDiffTask([
            'generation_status' => VersionDiffTask::STATUS_COMPLETED
        ]);

        $this->assertTrue($task->isCompleted());
    }

    public function test_is_completed_returns_false_when_status_is_not_completed()
    {
        $statuses = [
            VersionDiffTask::STATUS_PENDING,
            VersionDiffTask::STATUS_GENERATING,
            VersionDiffTask::STATUS_FAILED
        ];

        foreach ($statuses as $status) {
            $task = new VersionDiffTask([
                'generation_status' => $status
            ]);

            $this->assertFalse($task->isCompleted(), "isCompleted() should return false for status: $status");
        }
    }

    public function test_has_failed_returns_true_when_status_is_failed()
    {
        $task = new VersionDiffTask([
            'generation_status' => VersionDiffTask::STATUS_FAILED
        ]);

        $this->assertTrue($task->hasFailed());
    }

    public function test_has_failed_returns_false_when_status_is_not_failed()
    {
        $statuses = [
            VersionDiffTask::STATUS_PENDING,
            VersionDiffTask::STATUS_GENERATING,
            VersionDiffTask::STATUS_COMPLETED
        ];

        foreach ($statuses as $status) {
            $task = new VersionDiffTask([
                'generation_status' => $status
            ]);

            $this->assertFalse($task->hasFailed(), "hasFailed() should return false for status: $status");
        }
    }

    public function test_generation_status_constants_are_defined()
    {
        $this->assertEquals('pending', VersionDiffTask::STATUS_PENDING);
        $this->assertEquals('generating', VersionDiffTask::STATUS_GENERATING);
        $this->assertEquals('completed', VersionDiffTask::STATUS_COMPLETED);
        $this->assertEquals('failed', VersionDiffTask::STATUS_FAILED);
    }

    public function test_generation_status_is_in_fillable_array()
    {
        $task = new VersionDiffTask();
        $fillable = $task->getFillable();

        $this->assertContains('generation_status', $fillable);
    }

    public function test_generation_status_has_string_cast()
    {
        $task = new VersionDiffTask();
        $casts = $task->getCasts();

        $this->assertArrayHasKey('generation_status', $casts);
        $this->assertEquals('string', $casts['generation_status']);
    }
}
