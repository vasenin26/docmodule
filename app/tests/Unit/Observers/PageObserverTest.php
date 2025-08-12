<?php

namespace Tests\Unit\Observers;

use App\Jobs\CalculateVersionDifferenceJob;
use App\Models\Page;
use App\Models\User;
use App\Observers\PageObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PageObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_created_event_does_not_dispatch_job()
    {
        Queue::fake();

        $user = User::factory()->create();
        $page = Page::factory()->create([
            'created_by' => $user->id,
        ]);

        $observer = new PageObserver();
        $observer->created($page);

        Queue::assertNotPushed(CalculateVersionDifferenceJob::class);
    }

    public function test_updated_event_does_not_dispatch_job()
    {
        Queue::fake();

        $user = User::factory()->create();
        $page = Page::factory()->create([
            'created_by' => $user->id,
        ]);

        $observer = new PageObserver();
        $observer->updated($page);

        Queue::assertNotPushed(CalculateVersionDifferenceJob::class);
    }

    public function test_updated_event_does_not_dispatch_job_for_regular_update()
    {
        Queue::fake();

        $user = User::factory()->create();
        $page = Page::factory()->create([
            'created_by' => $user->id,
            'previous_version_id' => null,
        ]);

        // Проверяем, что job НЕ был запущен при создании
        Queue::assertNotPushed(CalculateVersionDifferenceJob::class);

        $observer = new PageObserver();
        $observer->updated($page);

        // Проверяем, что job не был запущен при обновлении
        Queue::assertNotPushed(CalculateVersionDifferenceJob::class);
    }

    public function test_deleted_event_does_not_dispatch_job()
    {
        Queue::fake();

        $user = User::factory()->create();
        $page = Page::factory()->create([
            'created_by' => $user->id,
        ]);

        // Проверяем, что job НЕ был запущен при создании
        Queue::assertNotPushed(CalculateVersionDifferenceJob::class);

        $observer = new PageObserver();
        $observer->deleted($page);

        // Проверяем, что job не был запущен при удалении
        Queue::assertNotPushed(CalculateVersionDifferenceJob::class);
    }

    public function test_restored_event_does_not_dispatch_job()
    {
        Queue::fake();

        $user = User::factory()->create();
        $page = Page::factory()->create([
            'created_by' => $user->id,
        ]);

        // Проверяем, что job НЕ был запущен при создании
        Queue::assertNotPushed(CalculateVersionDifferenceJob::class);

        $observer = new PageObserver();
        $observer->restored($page);

        // Проверяем, что job не был запущен при восстановлении
        Queue::assertNotPushed(CalculateVersionDifferenceJob::class);
    }

    public function test_force_deleted_event_does_not_dispatch_job()
    {
        Queue::fake();

        $user = User::factory()->create();
        $page = Page::factory()->create([
            'created_by' => $user->id,
        ]);

        // Проверяем, что job НЕ был запущен при создании
        Queue::assertNotPushed(CalculateVersionDifferenceJob::class);

        $observer = new PageObserver();
        $observer->forceDeleted($page);

        // Проверяем, что job не был запущен при принудительном удалении
        Queue::assertNotPushed(CalculateVersionDifferenceJob::class);
    }
}
