<?php

namespace Tests\Feature;

use App\Jobs\CalculateVersionDifferenceJob;
use App\Jobs\CreateTaskInTrackerJob;
use App\Jobs\GenerateTaskDescriptionJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PageVersioningWithTaskCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
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
        $pageId = \App\Models\Page::where('title', 'Original Page')->first()->id;

        // Обновляем страницу
        $response = $this->put("/pages/{$pageId}", [
            'title' => 'Updated Page',
            'content' => 'Updated content',
        ]);

        $response->assertRedirect('/pages');
        $response->assertSessionHas('success');

        // Проверяем, что Job был запущен через обсервер
        Queue::assertPushed(CalculateVersionDifferenceJob::class, function ($job) use ($pageId) {
            return $job->newVersionId > $pageId && $job->oldVersionId === $pageId;
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
        $job->handle();

        // Проверяем, что GenerateTaskDescriptionJob был запущен
        Queue::assertPushed(GenerateTaskDescriptionJob::class);

        // Симулируем выполнение GenerateTaskDescriptionJob
        $differenceData = [
            'new_version_id' => $page->id,
            'new_version_title' => $page->title,
            'new_version_content' => $page->content,
            'is_new_page' => true,
        ];
        $descriptionJob = new GenerateTaskDescriptionJob($differenceData);
        $descriptionJob->handle(app(\App\Services\TaskDescriptionGenerator\TaskDescriptionGeneratorInterface::class));

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
}
