<?php

namespace Tests\Unit\Services;

use App\Common\DTO\PageDataDTO;
use App\Interfaces\DraftServiceInterface;
use App\Models\Page;
use App\Models\PageVersion;
use App\Services\DraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DraftServiceTest extends TestCase
{
    use RefreshDatabase;

    private DraftServiceInterface $draftService;
    private Page $page;

    protected function setUp(): void
    {
        parent::setUp();
        $this->draftService = app(DraftServiceInterface::class);
        
        // Создаем тестовую страницу
        $this->page = Page::factory()->create([
            'version_id' => PageVersion::factory()->create()->id
        ]);
    }

    public function test_create_draft_successfully()
    {
        // Arrange
        $pageData = new PageDataDTO(
            title: 'Test Title',
            content: 'Test Content',
            files: ['test.pdf']
        );

        // Act
        $draft = $this->draftService->createDraft($this->page, $pageData);

        // Assert
        $this->assertInstanceOf(PageVersion::class, $draft);
        $this->assertEquals('Test Title', $draft->title);
        $this->assertEquals('Test Content', $draft->content);
        $this->assertEquals(['test.pdf'], $draft->files);
        $this->assertEquals($this->page->id, $draft->page_id);
    }

    public function test_get_current_draft_returns_null_when_no_draft()
    {
        // Act
        $draft = $this->draftService->getCurrentDraft($this->page);

        // Assert
        $this->assertNull($draft);
    }

    public function test_get_current_draft_returns_draft_when_exists()
    {
        // Arrange
        $draft = PageVersion::factory()->create([
            'page_id' => $this->page->id,
            'is_draft' => true
        ]);

        // Act
        $currentDraft = $this->draftService->getCurrentDraft($this->page);

        // Assert
        $this->assertInstanceOf(PageVersion::class, $currentDraft);
        $this->assertEquals($draft->id, $currentDraft->id);
    }

    public function test_delete_draft_returns_false_when_no_draft()
    {
        // Act
        $result = $this->draftService->deleteDraft($this->page);

        // Assert
        $this->assertFalse($result);
    }

    public function test_delete_draft_returns_true_when_draft_deleted()
    {
        // Arrange
        $draft = PageVersion::factory()->create([
            'page_id' => $this->page->id,
            'is_draft' => true
        ]);

        // Act
        $result = $this->draftService->deleteDraft($this->page);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('page_versions', ['id' => $draft->id]);
    }

    public function test_has_active_draft_returns_false_when_no_draft()
    {
        // Act
        $result = $this->draftService->hasActiveDraft($this->page);

        // Assert
        $this->assertFalse($result);
    }

    public function test_has_active_draft_returns_true_when_draft_exists()
    {
        // Arrange
        PageVersion::factory()->create([
            'page_id' => $this->page->id,
            'is_draft' => true
        ]);

        // Act
        $result = $this->draftService->hasActiveDraft($this->page);

        // Assert
        $this->assertTrue($result);
    }

    public function test_approve_draft_successfully()
    {
        // Arrange
        $draft = PageVersion::factory()->create([
            'page_id' => $this->page->id,
            'is_draft' => true
        ]);

        // Act
        $this->draftService->approveDraft($this->page, $draft);

        // Assert
        $this->assertDatabaseHas('page_versions', [
            'id' => $draft->id,
            'is_draft' => false
        ]);
    }

    public function test_create_draft_logs_activity()
    {
        // Arrange
        Log::shouldReceive('info')
            ->once()
            ->with('Creating draft for page', ['page_id' => $this->page->id]);

        $pageData = new PageDataDTO(
            title: 'Test Title',
            content: 'Test Content'
        );

        // Act
        $this->draftService->createDraft($this->page, $pageData);
    }

    public function test_delete_draft_logs_activity()
    {
        // Arrange
        $draft = PageVersion::factory()->create([
            'page_id' => $this->page->id,
            'is_draft' => true
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Draft deleted', ['page_id' => $this->page->id, 'draft_id' => $draft->id]);

        // Act
        $this->draftService->deleteDraft($this->page);
    }

    public function test_approve_draft_logs_activity()
    {
        // Arrange
        $draft = PageVersion::factory()->create([
            'page_id' => $this->page->id,
            'is_draft' => true
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Approving draft', ['page_id' => $this->page->id, 'draft_id' => $draft->id]);

        // Act
        $this->draftService->approveDraft($this->page, $draft);
    }
}
