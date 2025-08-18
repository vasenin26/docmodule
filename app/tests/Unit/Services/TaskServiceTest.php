<?php

namespace Tests\Unit\Services;

use App\Interfaces\TaskServiceInterface;
use App\Models\Page;
use App\Models\PageDiffDescription;
use App\Models\PageVersion;
use App\Services\TaskManagementService;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaskServiceInterface $taskService;
    private TaskManagementService $mockTaskManagementService;
    private Page $page;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем мок для TaskManagementService
        $this->mockTaskManagementService = Mockery::mock(TaskManagementService::class);
        
        // Создаем TaskService с моком
        $this->taskService = new TaskService($this->mockTaskManagementService);
        
        // Создаем тестовую страницу
        $this->page = Page::factory()->create([
            'version_id' => PageVersion::factory()->create()->id
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_task_for_page_successfully()
    {
        // Arrange
        $expectedTask = PageDiffDescription::factory()->make([
            'page_id' => $this->page->id
        ]);

        $this->mockTaskManagementService
            ->shouldReceive('createTaskForPage')
            ->once()
            ->with($this->page, null)
            ->andReturn($expectedTask);

        // Act
        $task = $this->taskService->createTaskForPage($this->page);

        // Assert
        $this->assertInstanceOf(PageDiffDescription::class, $task);
        $this->assertEquals($this->page->id, $task->page_id);
    }

    public function test_create_task_for_page_with_user_id()
    {
        // Arrange
        $userId = 123;
        $expectedTask = PageDiffDescription::factory()->make([
            'page_id' => $this->page->id,
            'created_by' => $userId
        ]);

        $this->mockTaskManagementService
            ->shouldReceive('createTaskForPage')
            ->once()
            ->with($this->page, $userId)
            ->andReturn($expectedTask);

        // Act
        $task = $this->taskService->createTaskForPage($this->page, $userId);

        // Assert
        $this->assertInstanceOf(PageDiffDescription::class, $task);
        $this->assertEquals($userId, $task->created_by);
    }

    public function test_can_create_task_for_page_returns_true()
    {
        // Arrange
        $this->mockTaskManagementService
            ->shouldReceive('canCreateTaskForPage')
            ->once()
            ->with($this->page)
            ->andReturn(true);

        // Act
        $result = $this->taskService->canCreateTaskForPage($this->page);

        // Assert
        $this->assertTrue($result);
    }

    public function test_can_create_task_for_page_returns_false()
    {
        // Arrange
        $this->mockTaskManagementService
            ->shouldReceive('canCreateTaskForPage')
            ->once()
            ->with($this->page)
            ->andReturn(false);

        // Act
        $result = $this->taskService->canCreateTaskForPage($this->page);

        // Assert
        $this->assertFalse($result);
    }

    public function test_create_task_for_page_propagates_exception()
    {
        // Arrange
        $exception = new \Exception('Task creation failed');

        $this->mockTaskManagementService
            ->shouldReceive('createTaskForPage')
            ->once()
            ->with($this->page, null)
            ->andThrow($exception);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Task creation failed');
        
        $this->taskService->createTaskForPage($this->page);
    }
}
