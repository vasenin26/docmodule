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

    public function test_created_event_dispatches_job()
    {
        Queue::fake();

        $user = User::factory()->create();
        $page = Page::factory()->create([
            'created_by' => $user->id,
        ]);

        $observer = new PageObserver();
        $observer->created($page);

        Queue::assertPushed(CalculateVersionDifferenceJob::class, function ($job) use ($page) {
            return $job->newVersionId === $page->id && $job->oldVersionId === null;
        });
    }

    public function test_updated_event_dispatches_job_for_new_version()
    {
        Queue::fake();

        $user = User::factory()->create();
        $oldPage = Page::factory()->create([
            'created_by' => $user->id,
        ]);

        $newPage = Page::factory()->create([
            'created_by' => $user->id,
            'previous_version_id' => $oldPage->id,
            'base_id' => $oldPage->id,
        ]);

        $observer = new PageObserver();
        $observer->updated($newPage);

        Queue::assertPushed(CalculateVersionDifferenceJob::class, function ($job) use ($newPage, $oldPage) {
            return $job->newVersionId === $newPage->id && $job->oldVersionId === $oldPage->id;
        });
    }

    public function test_updated_event_does_not_dispatch_job_for_regular_update()
    {
        Queue::fake();

        $user = User::factory()->create();
        $page = Page::factory()->create([
            'created_by' => $user->id,
            'previous_version_id' => null,
        ]);

        // Очищаем очередь от событий создания
        Queue::clear();

        $observer = new PageObserver();
        $observer->updated($page);

        Queue::assertNotPushed(CalculateVersionDifferenceJob::class);
    }

    public function test_deleted_event_does_not_dispatch_job()
    {
        Queue::fake();

        $user = User::factory()->create();
        $page = Page::factory()->create([
            'created_by' => $user->id,
        ]);

        // Очищаем очередь от событий создания
        Queue::clear();

        $observer = new PageObserver();
        $observer->deleted($page);

        Queue::assertNotPushed(CalculateVersionDifferenceJob::class);
    }

    public function test_restored_event_does_not_dispatch_job()
    {
        Queue::fake();

        $user = User::factory()->create();
        $page = Page::factory()->create([
            'created_by' => $user->id,
        ]);

        // Очищаем очередь от событий создания
        Queue::clear();

        $observer = new PageObserver();
        $observer->restored($page);

        Queue::assertNotPushed(CalculateVersionDifferenceJob::class);
    }

    public function test_force_deleted_event_does_not_dispatch_job()
    {
        Queue::fake();

        $user = User::factory()->create();
        $page = Page::factory()->create([
            'created_by' => $user->id,
        ]);

        // Очищаем очередь от событий создания
        Queue::clear();

        $observer = new PageObserver();
        $observer->forceDeleted($page);

        Queue::assertNotPushed(CalculateVersionDifferenceJob::class);
    }
}
