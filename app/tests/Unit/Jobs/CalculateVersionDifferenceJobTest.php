<?php

namespace Tests\Unit\Jobs;

use App\Jobs\CalculateVersionDifferenceJob;
use App\Jobs\GenerateTaskDescriptionJob;
use App\Models\Page;
use App\Models\User;
use App\Interfaces\DiffGeneratorInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CalculateVersionDifferenceJobTest extends TestCase
{
    use RefreshDatabase;

    private $diffGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->diffGenerator = $this->createMock(DiffGeneratorInterface::class);
    }

    public function test_job_calculates_difference_for_new_page()
    {
        Queue::fake();

        $user = User::factory()->create();
        $page = Page::factory()->create([
            'title' => 'Test Page',
            'content' => 'Test content',
            'created_by' => $user->id,
        ]);

        $this->diffGenerator->method('generateDiff')
            ->willReturnMap([
                ['', 'Test Page', 'title', "+ Test Page"],
                ['', 'Test content', 'content', "+ Test content"]
            ]);

        $job = new CalculateVersionDifferenceJob($page->id, null);
        $job->handle($this->diffGenerator);

        Queue::assertPushed(GenerateTaskDescriptionJob::class, function ($job) use ($page) {
            $differenceData = $job->differenceData;
            return $differenceData->newVersionTitle === 'Test Page' &&
                   $differenceData->isNewPage === true &&
                   $differenceData->diffOutput &&
                   str_contains($differenceData->diffOutput, '+ Test Page') &&
                   str_contains($differenceData->diffOutput, '+ Test content');
        });
    }

    public function test_job_calculates_difference_for_updated_page()
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

        $this->diffGenerator->method('generateDiff')
            ->willReturnMap([
                ['Old Title', 'New Title', 'title', "- Old Title\n+ New Title"],
                ['Old content', 'New content', 'content', "- Old content\n+ New content"]
            ]);

        $job = new CalculateVersionDifferenceJob($newPage->id, $oldPage->id);
        $job->handle($this->diffGenerator);

        Queue::assertPushed(GenerateTaskDescriptionJob::class, function ($job) use ($oldPage, $newPage) {
            $differenceData = $job->differenceData;
            return $differenceData->newVersionTitle === 'New Title' &&
                   $differenceData->titleChanged === true &&
                   $differenceData->contentChanged === true &&
                   $differenceData->isNewPage === false &&
                   $differenceData->diffOutput &&
                   str_contains($differenceData->diffOutput, '- Old Title') &&
                   str_contains($differenceData->diffOutput, '+ New Title') &&
                   str_contains($differenceData->diffOutput, '- Old content') &&
                   str_contains($differenceData->diffOutput, '+ New content');
        });
    }

    public function test_job_handles_missing_old_version()
    {
        Queue::fake();

        $user = User::factory()->create();
        $page = Page::factory()->create([
            'title' => 'Test Page',
            'content' => 'Test content',
            'created_by' => $user->id,
        ]);

        $this->diffGenerator->method('generateDiff')
            ->willReturnMap([
                ['', 'Test Page', 'title', "+ Test Page"],
                ['', 'Test content', 'content', "+ Test content"]
            ]);

        $job = new CalculateVersionDifferenceJob($page->id, 999);
        $job->handle($this->diffGenerator);

        Queue::assertPushed(GenerateTaskDescriptionJob::class, function ($job) {
            $differenceData = $job->differenceData;
            return $differenceData->isNewPage === true &&
                   $differenceData->diffOutput;
        });
    }

    public function test_job_generates_correct_diff_output_for_content_changes()
    {
        Queue::fake();

        $user = User::factory()->create();
        $oldPage = Page::factory()->create([
            'title' => 'Test Page',
            'content' => "Line 1\nLine 2\nLine 3",
            'created_by' => $user->id,
        ]);

        $newPage = Page::factory()->create([
            'title' => 'Test Page',
            'content' => "Line 1\nLine 2\nLine 4\nLine 5",
            'created_by' => $user->id,
            'previous_version_id' => $oldPage->id,
        ]);

        $this->diffGenerator->method('generateDiff')
            ->willReturnMap([
                ['Test Page', 'Test Page', 'title', ''],
                ["Line 1\nLine 2\nLine 3", "Line 1\nLine 2\nLine 4\nLine 5", 'content', "- Line 3\n+ Line 4\n+ Line 5"]
            ]);

        $job = new CalculateVersionDifferenceJob($newPage->id, $oldPage->id);
        $job->handle($this->diffGenerator);

        Queue::assertPushed(GenerateTaskDescriptionJob::class, function ($job) {
            $differenceData = $job->differenceData;
            $diffOutput = $differenceData->diffOutput;
            
            return str_contains($diffOutput, '- Line 3') &&
                   str_contains($diffOutput, '+ Line 4') &&
                   str_contains($diffOutput, '+ Line 5') &&
                   !str_contains($diffOutput, 'Line 1') &&
                   !str_contains($diffOutput, 'Line 2');
        });
    }

    public function test_job_generates_correct_diff_output_for_title_changes()
    {
        Queue::fake();

        $user = User::factory()->create();
        $oldPage = Page::factory()->create([
            'title' => 'Old Title',
            'content' => 'Same content',
            'created_by' => $user->id,
        ]);

        $newPage = Page::factory()->create([
            'title' => 'New Title',
            'content' => 'Same content',
            'created_by' => $user->id,
            'previous_version_id' => $oldPage->id,
        ]);

        $this->diffGenerator->method('generateDiff')
            ->willReturnMap([
                ['Old Title', 'New Title', 'title', "- Old Title\n+ New Title"],
                ['Same content', 'Same content', 'content', '']
            ]);

        $job = new CalculateVersionDifferenceJob($newPage->id, $oldPage->id);
        $job->handle($this->diffGenerator);

        Queue::assertPushed(GenerateTaskDescriptionJob::class, function ($job) {
            $differenceData = $job->differenceData;
            $diffOutput = $differenceData->diffOutput;
            
            return str_contains($diffOutput, '- Old Title') &&
                   str_contains($diffOutput, '+ New Title') &&
                   !str_contains($diffOutput, 'Same content');
        });
    }

    public function test_job_handles_empty_content_correctly()
    {
        Queue::fake();

        $user = User::factory()->create();
        $oldPage = Page::factory()->create([
            'title' => 'Test Page',
            'content' => '',
            'created_by' => $user->id,
        ]);

        $newPage = Page::factory()->create([
            'title' => 'Test Page',
            'content' => 'New content',
            'created_by' => $user->id,
            'previous_version_id' => $oldPage->id,
        ]);

        $this->diffGenerator->method('generateDiff')
            ->willReturnMap([
                ['Test Page', 'Test Page', 'title', ''],
                ['', 'New content', 'content', '+ New content']
            ]);

        $job = new CalculateVersionDifferenceJob($newPage->id, $oldPage->id);
        $job->handle($this->diffGenerator);

        Queue::assertPushed(GenerateTaskDescriptionJob::class, function ($job) {
            $differenceData = $job->differenceData;
            $diffOutput = $differenceData->diffOutput;
            
            return str_contains($diffOutput, '+ New content') &&
                   !str_contains($diffOutput, '-');
        });
    }

    public function test_job_generates_diff_output_in_git_format()
    {
        Queue::fake();

        $user = User::factory()->create();
        $oldPage = Page::factory()->create([
            'title' => 'Test Page',
            'content' => 'Old content',
            'created_by' => $user->id,
        ]);

        $newPage = Page::factory()->create([
            'title' => 'Test Page',
            'content' => 'New content',
            'created_by' => $user->id,
            'previous_version_id' => $oldPage->id,
        ]);

        $this->diffGenerator->method('generateDiff')
            ->willReturnMap([
                ['Test Page', 'Test Page', 'title', ''],
                ['Old content', 'New content', 'content', "- Old content\n+ New content"]
            ]);

        $job = new CalculateVersionDifferenceJob($newPage->id, $oldPage->id);
        $job->handle($this->diffGenerator);

        Queue::assertPushed(GenerateTaskDescriptionJob::class, function ($job) {
            $differenceData = $job->differenceData;
            $diffOutput = $differenceData->diffOutput;
            
            // Check that lines start with + or -
            $lines = explode("\n", trim($diffOutput));
            foreach ($lines as $line) {
                if (!empty($line)) {
                    if (!str_starts_with($line, '+') && !str_starts_with($line, '-')) {
                        return false;
                    }
                }
            }
            
            return str_contains($diffOutput, '- Old content') &&
                   str_contains($diffOutput, '+ New content');
        });
    }
}
