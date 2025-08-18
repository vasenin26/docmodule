<?php

namespace Tests\Unit\Services;

use App\Common\DTO\DraftApprovalResultDTO;
use App\Common\DTO\PageAggregateDTO;
use App\Common\DTO\PageDataDTO;
use App\Interfaces\DocumentationControlInterface;
use App\Interfaces\DraftServiceInterface;
use App\Interfaces\TaskServiceInterface;
use App\Models\Page;
use App\Models\PageDiffDescription;
use App\Models\PageVersion;
use App\Services\DocumentationControl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class DocumentationControlTest extends TestCase
{
    use RefreshDatabase;

    private DocumentationControlInterface $documentationControl;
    private DraftServiceInterface $mockDraftService;
    private TaskServiceInterface $mockTaskService;
    private Page $page;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем моки для зависимостей
        $this->mockDraftService = Mockery::mock(DraftServiceInterface::class);
        $this->mockTaskService = Mockery::mock(TaskServiceInterface::class);
        
        // Создаем DocumentationControl с моками
        $this->documentationControl = new DocumentationControl(
            $this->mockDraftService,
            $this->mockTaskService
        );
        
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

    public function test_update_page_with_draft_logic_updates_existing_draft()
    {
        // Arrange
        $pageData = new PageDataDTO(
            title: 'Updated Title',
            content: 'Updated Content',
            files: ['updated.pdf']
        );

        $existingDraft = PageVersion::factory()->make([
            'page_id' => $this->page->id,
            'is_draft' => true
        ]);

        $this->mockDraftService
            ->shouldReceive('getCurrentDraft')
            ->once()
            ->with($this->page)
            ->andReturn($existingDraft);

        $this->mockDraftService
            ->shouldReceive('createDraft')
            ->never();

        // Act
        $result = $this->documentationControl->updatePageWithDraftLogic($this->page, $pageData);

        // Assert
        $this->assertInstanceOf(PageVersion::class, $result);
        $this->assertEquals($existingDraft->id, $result->id);
    }

    public function test_update_page_with_draft_logic_creates_new_draft()
    {
        // Arrange
        $pageData = new PageDataDTO(
            title: 'New Title',
            content: 'New Content',
            files: ['new.pdf']
        );

        $newDraft = PageVersion::factory()->make([
            'page_id' => $this->page->id,
            'is_draft' => true
        ]);

        $this->mockDraftService
            ->shouldReceive('getCurrentDraft')
            ->once()
            ->with($this->page)
            ->andReturn(null);

        $this->mockDraftService
            ->shouldReceive('createDraft')
            ->once()
            ->with($this->page, $pageData)
            ->andReturn($newDraft);

        // Act
        $result = $this->documentationControl->updatePageWithDraftLogic($this->page, $pageData);

        // Assert
        $this->assertInstanceOf(PageVersion::class, $result);
        $this->assertEquals($newDraft->id, $result->id);
    }

    public function test_approve_draft_with_task_successfully()
    {
        // Arrange
        $draft = PageVersion::factory()->make([
            'page_id' => $this->page->id,
            'is_draft' => true
        ]);

        $task = PageDiffDescription::factory()->make([
            'page_id' => $this->page->id
        ]);

        $this->mockDraftService
            ->shouldReceive('getCurrentDraft')
            ->once()
            ->with($this->page)
            ->andReturn($draft);

        $this->mockDraftService
            ->shouldReceive('approveDraft')
            ->once()
            ->with($this->page, $draft);

        $this->mockTaskService
            ->shouldReceive('createTaskForPage')
            ->once()
            ->with($this->page)
            ->andReturn($task);

        // Act
        $result = $this->documentationControl->approveDraftWithTask($this->page, true);

        // Assert
        $this->assertInstanceOf(DraftApprovalResultDTO::class, $result);
        $this->assertTrue($result->success);
        $this->assertTrue($result->taskCreated);
        $this->assertEquals($task->id, $result->taskId);
    }

    public function test_approve_draft_without_task()
    {
        // Arrange
        $draft = PageVersion::factory()->make([
            'page_id' => $this->page->id,
            'is_draft' => true
        ]);

        $this->mockDraftService
            ->shouldReceive('getCurrentDraft')
            ->once()
            ->with($this->page)
            ->andReturn($draft);

        $this->mockDraftService
            ->shouldReceive('approveDraft')
            ->once()
            ->with($this->page, $draft);

        $this->mockTaskService
            ->shouldReceive('createTaskForPage')
            ->never();

        // Act
        $result = $this->documentationControl->approveDraftWithTask($this->page, false);

        // Assert
        $this->assertInstanceOf(DraftApprovalResultDTO::class, $result);
        $this->assertTrue($result->success);
        $this->assertFalse($result->taskCreated);
        $this->assertNull($result->taskId);
    }

    public function test_approve_draft_throws_exception_when_no_draft()
    {
        // Arrange
        $this->mockDraftService
            ->shouldReceive('getCurrentDraft')
            ->once()
            ->with($this->page)
            ->andReturn(null);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Черновик не найден.');
        
        $this->documentationControl->approveDraftWithTask($this->page, false);
    }

    public function test_approve_draft_with_task_error()
    {
        // Arrange
        $draft = PageVersion::factory()->make([
            'page_id' => $this->page->id,
            'is_draft' => true
        ]);

        $exception = new \Exception('Task creation failed');

        $this->mockDraftService
            ->shouldReceive('getCurrentDraft')
            ->once()
            ->with($this->page)
            ->andReturn($draft);

        $this->mockDraftService
            ->shouldReceive('approveDraft')
            ->once()
            ->with($this->page, $draft);

        $this->mockTaskService
            ->shouldReceive('createTaskForPage')
            ->once()
            ->with($this->page)
            ->andThrow($exception);

        // Act
        $result = $this->documentationControl->approveDraftWithTask($this->page, true);

        // Assert
        $this->assertInstanceOf(DraftApprovalResultDTO::class, $result);
        $this->assertTrue($result->success);
        $this->assertFalse($result->taskCreated);
        $this->assertNotNull($result->taskError);
        $this->assertEquals('Task creation failed', $result->taskError);
    }

    public function test_get_current_page_aggregate()
    {
        // Arrange
        $currentDraft = PageVersion::factory()->make([
            'page_id' => $this->page->id,
            'is_draft' => true
        ]);

        $this->mockDraftService
            ->shouldReceive('getCurrentDraft')
            ->once()
            ->with($this->page)
            ->andReturn($currentDraft);

        $this->mockDraftService
            ->shouldReceive('hasActiveDraft')
            ->once()
            ->with($this->page)
            ->andReturn(true);

        $this->mockTaskService
            ->shouldReceive('canCreateTaskForPage')
            ->once()
            ->with($this->page)
            ->andReturn(true);

        // Мокаем методы страницы
        $this->page->shouldReceive('getActualizationInfo')->andReturn(null);
        $this->page->shouldReceive('hasActiveActualization')->andReturn(false);
        $this->page->shouldReceive('isActualized')->andReturn(false);

        // Act
        $result = $this->documentationControl->getCurrentPageAggregate($this->page);

        // Assert
        $this->assertInstanceOf(PageAggregateDTO::class, $result);
        $this->assertEquals($this->page, $result->page);
        $this->assertEquals($currentDraft, $result->currentDraft);
        $this->assertTrue($result->hasActiveDraft);
        $this->assertTrue($result->canCreateTask);
    }

    public function test_update_page_with_draft_logic_logs_activity()
    {
        // Arrange
        $pageData = new PageDataDTO(
            title: 'Test Title',
            content: 'Test Content'
        );

        $existingDraft = PageVersion::factory()->make([
            'page_id' => $this->page->id,
            'is_draft' => true
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Updating page with draft logic', ['page_id' => $this->page->id]);

        Log::shouldReceive('info')
            ->once()
            ->with('Existing draft updated', ['draft_id' => $existingDraft->id]);

        $this->mockDraftService
            ->shouldReceive('getCurrentDraft')
            ->once()
            ->with($this->page)
            ->andReturn($existingDraft);

        // Act
        $this->documentationControl->updatePageWithDraftLogic($this->page, $pageData);
    }

    public function test_approve_draft_with_task_logs_activity()
    {
        // Arrange
        $draft = PageVersion::factory()->make([
            'page_id' => $this->page->id,
            'is_draft' => true
        ]);

        $task = PageDiffDescription::factory()->make([
            'page_id' => $this->page->id
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Approving draft with task', ['page_id' => $this->page->id, 'create_task' => true]);

        Log::shouldReceive('info')
            ->once()
            ->with('Task created for approved draft', ['task_id' => $task->id]);

        $this->mockDraftService
            ->shouldReceive('getCurrentDraft')
            ->once()
            ->with($this->page)
            ->andReturn($draft);

        $this->mockDraftService
            ->shouldReceive('approveDraft')
            ->once()
            ->with($this->page, $draft);

        $this->mockTaskService
            ->shouldReceive('createTaskForPage')
            ->once()
            ->with($this->page)
            ->andReturn($task);

        // Act
        $this->documentationControl->approveDraftWithTask($this->page, true);
    }
}
