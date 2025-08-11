<?php

namespace Tests\Feature;

use App\Jobs\CalculateVersionDifferenceJob;
use App\Jobs\CreateTaskInTrackerJob;
use App\Jobs\GenerateTaskDescriptionJob;
use App\Models\User;
use App\Services\DiffGenerator\DiffGeneratorInterface;
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
            ->willReturnMap([
                ['', 'Test Page', 'title', '+ Test Page'],
                ['', 'Test content', 'content', '+ Test content'],
                ['Original Page', 'Updated Page', 'title', '- Original Page\n+ Updated Page'],
                ['Original content', 'Updated content', 'content', '- Original content\n+ Updated content'],
                ['', 'First Page', 'title', '+ First Page'],
                ['', 'First content', 'content', '+ First content'],
                ['', 'Second Page', 'title', '+ Second Page'],
                ['', 'Second content', 'content', '+ Second content']
            ]);
    }

    public function test_creating_new_page_dispatches_task_creation_jobs()
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

        // Проверяем, что Job был запущен через обсервер
        Queue::assertPushed(CalculateVersionDifferenceJob::class, function ($job) {
            return $job->newVersionId > 0 && $job->oldVersionId === null;
        });
    }

    public function test_updating_page_dispatches_task_creation_jobs()
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        // Создаем страницу
        $page = $this->post('/pages', [
            'title' => 'Original Page',
            'content' => 'Original content',
        ]);

        // Получаем ID созданной страницы
        $originalPage = \App\Models\Page::where('title', 'Original Page')->first();
        $pageId = $originalPage->id;

        // Обновляем страницу
        $response = $this->put("/pages/{$pageId}", [
            'title' => 'Updated Page',
            'content' => 'Updated content',
        ]);

        $response->assertRedirect('/pages');
        $response->assertSessionHas('success');

        // Проверяем, что была создана новая версия
        $newVersion = \App\Models\Page::where('title', 'Updated Page')->first();
        $this->assertNotNull($newVersion);
        $this->assertNotEquals($pageId, $newVersion->id);
        $this->assertEquals($pageId, $newVersion->previous_version_id);

        // Проверяем, что Job был запущен через обсервер для новой версии
        Queue::assertPushed(CalculateVersionDifferenceJob::class, function ($job) use ($newVersion, $pageId) {
            return $job->newVersionId === $newVersion->id && $job->oldVersionId === $pageId;
        });
    }

    public function test_job_chain_executes_in_correct_order()
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        // Создаем страницу
        $this->post('/pages', [
            'title' => 'Test Page',
            'content' => 'Test content',
        ]);

        // Проверяем, что CalculateVersionDifferenceJob был запущен через обсервер
        Queue::assertPushed(CalculateVersionDifferenceJob::class);

        // Симулируем выполнение CalculateVersionDifferenceJob
        $page = \App\Models\Page::where('title', 'Test Page')->first();
        $job = new CalculateVersionDifferenceJob($page->id, null);
        $job->handle($this->diffGenerator);

        // Проверяем, что GenerateTaskDescriptionJob был запущен
        Queue::assertPushed(GenerateTaskDescriptionJob::class);

        // Симулируем выполнение GenerateTaskDescriptionJob
        $differenceData = [
            'new_version_id' => $page->id,
            'new_version_title' => $page->title,
            'new_version_content' => $page->content,
            'is_new_page' => true,
            'diff_output' => "+ {$page->title}\n+ {$page->content}",
        ];
        $descriptionJob = new GenerateTaskDescriptionJob($differenceData);
        $descriptionJob->handle(app(\App\Interfaces\TaskDescriptionGeneratorInterface::class));

        // Проверяем, что CreateTaskInTrackerJob был запущен
        Queue::assertPushed(CreateTaskInTrackerJob::class, function ($job) {
            return $job->title === 'New page created: Test Page';
        });
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

        // Проверяем, что Job был запущен через обсервер
        Queue::assertPushed(CalculateVersionDifferenceJob::class);
    }

    public function test_multiple_page_operations_create_multiple_tasks()
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        // Создаем первую страницу
        $this->post('/pages', [
            'title' => 'First Page',
            'content' => 'First content',
        ]);

        // Создаем вторую страницу
        $this->post('/pages', [
            'title' => 'Second Page',
            'content' => 'Second content',
        ]);

        // Проверяем, что было запущено два Job'а через обсерверы
        Queue::assertPushed(CalculateVersionDifferenceJob::class, 2);
    }

    public function test_diff_output_is_passed_to_description_generator()
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        // Создаем страницу
        $this->post('/pages', [
            'title' => 'Test Page',
            'content' => 'Test content',
        ]);

        // Получаем созданную страницу
        $page = \App\Models\Page::where('title', 'Test Page')->first();

        // Симулируем выполнение CalculateVersionDifferenceJob
        $job = new CalculateVersionDifferenceJob($page->id, null);
        $job->handle($this->diffGenerator);

        // Проверяем, что GenerateTaskDescriptionJob получил diff_output
        Queue::assertPushed(GenerateTaskDescriptionJob::class, function ($job) {
            $differenceData = $job->differenceData;
            return isset($differenceData['diff_output']) &&
                   str_contains($differenceData['diff_output'], '+ Test Page') &&
                   str_contains($differenceData['diff_output'], '+ Test content');
        });
    }
}
