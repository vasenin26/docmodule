<?php

namespace App\Services;

use App\Common\DTO\PageVersionDTO;
use App\Interfaces\DocumentationControlInterface;
use App\Interfaces\DraftServiceInterface;
use App\Interfaces\TaskServiceInterface;
use App\Models\Page;
use App\Models\PageVersion;
use App\Common\DTO\PageDataDTO;
use App\Common\DTO\DraftApprovalResultDTO;
use App\Common\DTO\PageAggregateDTO;
use App\Common\DTO\PageListDTO;
use App\Common\DTO\PageDetailDTO;
use App\Common\DTO\PageEditDTO;
use App\Common\DTO\DraftDTO;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class DocumentationControl implements DocumentationControlInterface
{
    public function __construct(
        private DraftServiceInterface $draftService,
        private TaskServiceInterface $taskService
    ) {}

    public function getCurrentVersion(Page $page): PageVersionDTO
    {
        $page->load(['currentVersion']);

        return new PageVersionDTO(
            page_id: $page->id,
            version_id: $page->currentVersion->id,
            title: $page->currentVersion->title,
            content: $page->currentVersion->content,
            files: $page->currentVersion->files,
            created_at: $page->currentVersion->created_at
        );
    }

    public function getPageVersion(Page $page, int $id): PageVersionDTO
    {
        $version = $page->getVersion($id);

        return new PageVersionDTO(
            page_id: $page->id,
            version_id: $version->id,
            title: $version->title,
            content: $version->content,
            files: $version->files,
            created_at: $version->created_at
        );
    }

    public function updatePageWithDraftLogic(Page $page, PageDataDTO $data): PageVersion
    {
        Log::info('Updating page with draft logic', ['page_id' => $page->id]);

        $currentDraft = $this->draftService->getCurrentDraft($page);

        if (!$currentDraft) {
            throw new \Exception('Черновик не найден. Создайте черновик перед редактированием.');
        }

        // Обновляем существующий черновик
        $currentDraft->update($data->toArray());
        Log::info('Existing draft updated', ['draft_id' => $currentDraft->id]);
        return $currentDraft;
    }

    // Новый метод для создания черновика из текущей версии
    public function createDraftFromCurrentVersion(Page $page, PageDataDTO $data): PageVersion
    {
        Log::info('Creating draft from current version', ['page_id' => $page->id]);

        // Создаем новый черновик на основе данных формы
        $draft = $this->draftService->createDraft($page, $data);

        Log::info('Draft created from current version', ['draft_id' => $draft->id]);
        return $draft;
    }

    public function approveDraftWithTask(Page $page, bool $createTask = false): DraftApprovalResultDTO
    {
        Log::info('Approving draft with task', ['page_id' => $page->id, 'create_task' => $createTask]);

        $draft = $this->draftService->getCurrentDraft($page);

        if (!$draft) {
            throw new \Exception('Черновик не найден.');
        }

        // Утверждаем черновик
        $this->draftService->approveDraft($page, $draft);

        // Создаем задачу только если требуется
        if ($createTask) {
            try {
                $task = $this->taskService->createTaskForPage($page);
                Log::info('Task created for approved draft', ['task_id' => $task->id]);
                return DraftApprovalResultDTO::withTask(
                    'Черновик утвержден. Задача создана и обрабатывается.',
                    $task->id
                );
            } catch (\Exception $e) {
                Log::error('Failed to create task for approved draft', ['error' => $e->getMessage()]);
                return DraftApprovalResultDTO::withTaskError(
                    'Черновик утвержден, но не удалось создать задачу: ' . $e->getMessage(),
                    $e->getMessage()
                );
            }
        }

        return DraftApprovalResultDTO::success('Черновик утвержден.');
    }

    public function getCurrentPageAggregate(Page $page): PageAggregateDTO
    {
        $currentDraft = $this->draftService->getCurrentDraft($page);
        $hasActiveDraft = $this->draftService->hasActiveDraft($page);
        $canCreateTask = $this->taskService->canCreateTaskForPage($page);

        return new PageAggregateDTO(
            page: $page,
            currentDraft: $currentDraft,
            hasActiveDraft: $hasActiveDraft,
            canCreateTask: $canCreateTask,
            actualizationInfo: $page->getActualizationInfo(),
            hasActiveActualization: $page->hasActiveActualization(),
            isActualized: $page->isActualized()
        );
    }

    public function getPageListDTO(LengthAwarePaginator $paginator, array $filters = []): PageListDTO
    {
        return PageListDTO::fromPaginator($paginator, $filters);
    }

    public function getPageDetailDTO(Page $page): PageDetailDTO
    {
        // Добавляем необходимые поля к странице
        $page->currentDraft = $this->draftService->getCurrentDraft($page);
        $page->hasActiveDraft = $this->draftService->hasActiveDraft($page);
        $page->canCreateTask = $this->taskService->canCreateTaskForPage($page);
        $page->hasActiveActualization = $page->hasActiveActualization();
        $page->isActualized = $page->isActualized();
        $page->actualizationInfo = $page->getActualizationInfo();

        return PageDetailDTO::fromPage($page);
    }

    public function getPageEditDTO(Page $page): PageEditDTO
    {
        // Добавляем необходимые поля к странице
        $page->currentDraft = $this->draftService->getCurrentDraft($page);
        $page->hasActiveDraft = $this->draftService->hasActiveDraft($page);
        $page->canCreateTask = $this->taskService->canCreateTaskForPage($page);

        Log::info('Getting PageEditDTO', [
            'page_id' => $page->id,
            'has_current_draft' => $page->currentDraft !== null,
            'has_active_draft' => $page->hasActiveDraft,
            'can_create_task' => $page->canCreateTask,
        ]);

        return PageEditDTO::fromPage($page);
    }

    public function getDraftDTO(PageVersion $draft): DraftDTO
    {
        return DraftDTO::fromPageVersion($draft);
    }
}
