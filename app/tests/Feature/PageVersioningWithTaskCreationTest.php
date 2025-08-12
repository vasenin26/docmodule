<?php

namespace Tests\Feature;

use App\Interfaces\DiffGeneratorInterface;
use App\Jobs\CalculateVersionDifferenceJob;
use App\Jobs\CreateTaskInTrackerJob;
use App\Jobs\GenerateTaskDescriptionJob;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PageVersioningWithTaskCreationTest extends TestCase
{
    use RefreshDatabase;

    private $diffGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();

        // Создаем мок для DiffGeneratorInterface
        $this->diffGenerator = $this->createMock(DiffGeneratorInterface::class);
        $this->diffGenerator->method('generateDiff')
            ->willReturnCallback(function ($oldContent, $newContent, $type = 'content') {
                if ($oldContent === '' && $newContent === 'Test Page') return '+ Test Page';
                if ($oldContent === '' && $newContent === 'Test content') return '+ Test content';
                if ($oldContent === 'Original Page' && $newContent === 'Updated Page') return '- Original Page\n+ Updated Page';
                if ($oldContent === 'Original content' && $newContent === 'Updated content') return '- Original content\n+ Updated content';
                if ($oldContent === '' && $newContent === 'First Page') return '+ First Page';
                if ($oldContent === '' && $newContent === 'First content') return '+ First content';
                if ($oldContent === '' && $newContent === 'Second Page') return '+ Second Page';
                if ($oldContent === '' && $newContent === 'Second content') return '+ Second content';
                if ($oldContent === '' && $newContent === 'Draft Title') return '+ Draft Title';
                if ($oldContent === '' && $newContent === 'Draft content') return '+ Draft content';
                if ($oldContent === 'Original Page' && $newContent === 'Draft Title') return '- Original Page\n+ Draft Title';
                if ($oldContent === 'Original content' && $newContent === 'Draft content') return '- Original content\n+ Draft content';
                
                // Default fallback
                return "+ {$newContent}";
            });
    }

    public function test_creating_page_does_not_dispatch_task_creation_jobs()
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/pages', [
            'title' => 'Test Page',
            'content' => 'Test content',
        ]);

        $response->assertRedirect('/pages');
        $response->assertSessionHas('success');

        // Проверяем, что Job НЕ был запущен при создании страницы
        Queue::assertNotPushed(CalculateVersionDifferenceJob::class);
    }



    public function test_job_chain_executes_in_correct_order_when_draft_approved()
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        // Создаем страницу и черновик
        $page = Page::factory()->create(['created_by' => $user->id]);
        $draft = $page->createDraft(['title' => 'Draft Title']);

        // Утверждаем черновик
        $response = $this->post(route('pages.draft.approve', $draft->id));

        // Проверяем, что CalculateVersionDifferenceJob был запущен при утверждении
        Queue::assertPushed(CalculateVersionDifferenceJob::class);

        // Симулируем выполнение CalculateVersionDifferenceJob
        $job = new CalculateVersionDifferenceJob($draft->id, $page->id);
        $job->handle($this->diffGenerator);

        // Проверяем, что GenerateTaskDescriptionJob был запущен
        Queue::assertPushed(GenerateTaskDescriptionJob::class);

        // Симулируем выполнение GenerateTaskDescriptionJob
        $differenceData = new \App\Common\DTO\DifferenceDataDTO(
            diffOutput: "+ Draft Title\n+ Draft content",
            newVersionTitle: $draft->title,
            isNewPage: false
        );
        $descriptionJob = new GenerateTaskDescriptionJob($differenceData);
        $descriptionJob->handle(app(\App\Interfaces\TaskDescriptionGeneratorInterface::class));

        // Проверяем, что CreateTaskInTrackerJob был запущен
        Queue::assertPushed(CreateTaskInTrackerJob::class);
    }

    public function test_task_creation_does_not_block_page_operations()
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        // Создаем страницу
        $response = $this->post('/pages', [
            'title' => 'Test Page',
            'content' => 'Test content',
        ]);

        // Проверяем, что страница была создана успешно
        $response->assertRedirect('/pages');
        $response->assertSessionHas('success');

        // Проверяем, что страница существует в базе данных
        $this->assertDatabaseHas('pages', [
            'title' => 'Test Page',
            'content' => 'Test content',
        ]);

        // Проверяем, что Job НЕ был запущен при создании страницы
        Queue::assertNotPushed(CalculateVersionDifferenceJob::class);
    }

    public function test_multiple_draft_approvals_create_multiple_tasks()
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        // Создаем первую страницу и черновик
        $page1 = Page::factory()->create(['created_by' => $user->id]);
        $draft1 = $page1->createDraft(['title' => 'First Draft']);

        // Создаем вторую страницу и черновик
        $page2 = Page::factory()->create(['created_by' => $user->id]);
        $draft2 = $page2->createDraft(['title' => 'Second Draft']);

        // Утверждаем первый черновик
        $this->post(route('pages.draft.approve', $draft1->id));

        // Утверждаем второй черновик
        $this->post(route('pages.draft.approve', $draft2->id));

        // Проверяем, что было запущено два Job'а при утверждении черновиков
        Queue::assertPushed(CalculateVersionDifferenceJob::class, 2);
    }

    public function test_diff_output_is_passed_to_description_generator()
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        // Создаем страницу и черновик
        $page = Page::factory()->create(['created_by' => $user->id]);
        $draft = $page->createDraft(['title' => 'Draft Title', 'content' => 'Draft content']);

        // Утверждаем черновик
        $this->post(route('pages.draft.approve', $draft->id));

        // Симулируем выполнение CalculateVersionDifferenceJob
        $job = new CalculateVersionDifferenceJob($draft->id, $page->id);
        $job->handle($this->diffGenerator);

        // Проверяем, что GenerateTaskDescriptionJob получил diff_output
        Queue::assertPushed(GenerateTaskDescriptionJob::class, function ($job) {
            $differenceData = $job->differenceData;
            return $differenceData->diffOutput &&
                   str_contains($differenceData->diffOutput, '+ Draft Title') &&
                   str_contains($differenceData->diffOutput, '+ Draft content');
        });
    }
}
