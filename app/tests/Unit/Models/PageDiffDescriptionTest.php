<?php

namespace Tests\Unit\Models;

use App\Models\PageDiffDescription;
use Tests\TestCase;

class PageDiffDescriptionTest extends TestCase
{
    public function test_is_generating_returns_true_when_status_is_generating()
    {
        $task = new PageDiffDescription([
            'generation_status' => PageDiffDescription::STATUS_GENERATING
        ]);

        $this->assertTrue($task->isGenerating());
    }

    public function test_is_generating_returns_false_when_status_is_not_generating()
    {
        $statuses = [
            PageDiffDescription::STATUS_PENDING,
            PageDiffDescription::STATUS_COMPLETED,
            PageDiffDescription::STATUS_FAILED
        ];

        foreach ($statuses as $status) {
            $task = new PageDiffDescription([
                'generation_status' => $status
            ]);

            $this->assertFalse($task->isGenerating(), "isGenerating() should return false for status: $status");
        }
    }

    public function test_is_completed_returns_true_when_status_is_completed()
    {
        $task = new PageDiffDescription([
            'generation_status' => PageDiffDescription::STATUS_COMPLETED
        ]);

        $this->assertTrue($task->isCompleted());
    }

    public function test_is_completed_returns_false_when_status_is_not_completed()
    {
        $statuses = [
            PageDiffDescription::STATUS_PENDING,
            PageDiffDescription::STATUS_GENERATING,
            PageDiffDescription::STATUS_FAILED
        ];

        foreach ($statuses as $status) {
            $task = new PageDiffDescription([
                'generation_status' => $status
            ]);

            $this->assertFalse($task->isCompleted(), "isCompleted() should return false for status: $status");
        }
    }

    public function test_has_failed_returns_true_when_status_is_failed()
    {
        $task = new PageDiffDescription([
            'generation_status' => PageDiffDescription::STATUS_FAILED
        ]);

        $this->assertTrue($task->hasFailed());
    }

    public function test_has_failed_returns_false_when_status_is_not_failed()
    {
        $statuses = [
            PageDiffDescription::STATUS_PENDING,
            PageDiffDescription::STATUS_GENERATING,
            PageDiffDescription::STATUS_COMPLETED
        ];

        foreach ($statuses as $status) {
            $task = new PageDiffDescription([
                'generation_status' => $status
            ]);

            $this->assertFalse($task->hasFailed(), "hasFailed() should return false for status: $status");
        }
    }

    public function test_generation_status_constants_are_defined()
    {
        $this->assertEquals('pending', PageDiffDescription::STATUS_PENDING);
        $this->assertEquals('generating', PageDiffDescription::STATUS_GENERATING);
        $this->assertEquals('completed', PageDiffDescription::STATUS_COMPLETED);
        $this->assertEquals('failed', PageDiffDescription::STATUS_FAILED);
    }

    public function test_generation_status_is_in_fillable_array()
    {
        $task = new PageDiffDescription();
        $fillable = $task->getFillable();

        $this->assertContains('generation_status', $fillable);
    }

    public function test_generation_status_has_string_cast()
    {
        $task = new PageDiffDescription();
        $casts = $task->getCasts();

        $this->assertArrayHasKey('generation_status', $casts);
        $this->assertEquals('string', $casts['generation_status']);
    }
}
