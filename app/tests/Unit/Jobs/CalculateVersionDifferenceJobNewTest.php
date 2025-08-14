<?php

namespace Tests\Unit\Jobs;

use App\Interfaces\ContentGenerator\DiffGeneratorInterface;
use App\Jobs\CalculateVersionDifferenceJob;
use App\Jobs\GenerateTaskDescriptionJob;
use App\Models\Page;
use App\Models\PageDiffDescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CalculateVersionDifferenceJobNewTest extends TestCase
{
    use RefreshDatabase;

    private $diffGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->diffGenerator = $this->createMock(DiffGeneratorInterface::class);
    }

    public function test_job_creates_page_diff_description_and_dispatches_next_job()
    {
        Queue::fake();

        $user = User::factory()->create();
        $page = Page::factory()->create([
            'title' => 'Test Page',
            'content' => 'Test content',
            'created_by' => $user->id,
        ]);

        $job = new CalculateVersionDifferenceJob($page->id, null);
        $job->handle($this->diffGenerator);

        // Check that PageDiffDescription was created
        $this->assertDatabaseHas('page_diff_descriptions', [
            'page_id' => $page->id,
            'created_by' => $user->id,
            'generation_status' => PageDiffDescription::STATUS_PENDING,
            'content' => null
        ]);

        // Check that GenerateTaskDescriptionJob was dispatched
        Queue::assertPushed(GenerateTaskDescriptionJob::class, function ($job) {
            return is_int($job->pageDiffDescriptionId) && $job->pageDiffDescriptionId > 0;
        });
    }

    public function test_job_creates_page_diff_description_for_page_update()
    {
        Queue::fake();

        $user = User::factory()->create();

        $oldPage = Page::factory()->create([
            'title' => 'Old Title',
            'content' => 'Old content',
            'created_by' => $user->id,
        ]);

        $newPage = Page::factory()->create([
            'title' => 'New Title',
            'content' => 'New content',
            'created_by' => $user->id,
            'previous_version_id' => $oldPage->id,
        ]);

        $job = new CalculateVersionDifferenceJob($newPage->id, $oldPage->id);
        $job->handle($this->diffGenerator);

        // Check that PageDiffDescription was created
        $this->assertDatabaseHas('page_diff_descriptions', [
            'page_id' => $newPage->id,
            'created_by' => $user->id,
            'generation_status' => PageDiffDescription::STATUS_PENDING
        ]);

        // Check that GenerateTaskDescriptionJob was dispatched
        Queue::assertPushed(GenerateTaskDescriptionJob::class);
    }

    public function test_job_handles_missing_page_gracefully()
    {
        Queue::fake();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $job = new CalculateVersionDifferenceJob(999, null);
        $job->handle($this->diffGenerator);
    }
}
