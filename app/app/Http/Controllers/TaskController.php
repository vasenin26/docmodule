<?php

namespace App\Http\Controllers;

use App\Common\Enums\AgentTaskType;
use App\Common\Enums\GenerationStatus;
use App\Factory\PromptProviderFactory;
use App\Interfaces\Factory\LLMChatFactoryInterface;
use App\Jobs\GenerateTaskDescriptionJob;
use App\Jobs\GenerateTechplaneJob;
use App\Models\AgentTask;
use App\Models\VersionDiffTask;
use App\Models\Techplane;
use App\Models\LLMChat;
use App\Models\Project;
use App\Http\Requests\TaskUpdateRequest;
use App\Http\Requests\SendTaskMessageRequest;
use App\Common\DTO\SendMessageDTO;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Messages\SystemMessage;
use Vasenin26\Conversation\Messages\UserMessage;
use Vasenin26\Conversation\Factory\ConversationFactory;
use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use App\Interfaces\AgentTaskManagerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\PageVersion;

class TaskController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            'auth',
            'verified',
        ];
    }

    /**
     * Display a listing of tasks.
     */
    public function index(Request $request, ?Project $project = null): Response
    {
        $query = VersionDiffTask::with(['creator', 'pageVersion.page'])
            ->orderBy('created_at', 'desc');

        // Фильтрация по проекту если указан
        if ($project) {
            $query->where('project_id', $project->id);
        }

        // Применение фильтров
        if ($request->filled('status')) {
            $query->where('generation_status', $request->status);
        }

        if ($request->filled('search')) {
            $query->whereHas('pageVersion.page', function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%');
            });
        }

        $tasks = $query->paginate(15);

        return Inertia::render('tasks/Index', [
            'tasks' => $tasks,
            'project' => $project,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show create form for task within project.
     */
    public function create(Project $project): Response
    {
        if (!$project->canAccess(Auth::user())) {
            abort(403);
        }

        return Inertia::render('tasks/Create', [
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
            ],
        ]);
    }

    /**
     * Display the specified task.
     */
    public function show(VersionDiffTask $task): Response
    {
        $task->load(['pageVersion.page', 'techplane', 'llmChat', 'creator', 'pageVersions.page']);

        $pageVersion = $task->pageVersion;
        $page = $pageVersion?->page;

        return Inertia::render('tasks/Show', [
            'task' => [
                'id' => $task->id,
                'content' => $task->content,
                'generation_status' => $task->generationStatus(),
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
                'edited_at' => $task->edited_at,
                'pageVersion' => $pageVersion ? [
                    'id' => $pageVersion->id,
                    'title' => $pageVersion->title,
                    'content' => $pageVersion->content,
                    'created_at' => $pageVersion->created_at,
                    'page' => $page ? [
                        'id' => $page->id,
                        'title' => $page->title,
                        'content' => $page->content,
                        'created_at' => $page->created_at,
                    ] : null,
                    'previousVersion' => $pageVersion->previousVersion ? [
                        'id' => $pageVersion->previousVersion->id,
                        'title' => $pageVersion->previousVersion->title,
                        'content' => $pageVersion->previousVersion->content,
                        'created_at' => $pageVersion->previousVersion->created_at,
                    ] : null,
                ] : null,
                'creator' => $task->creator ? [
                    'id' => $task->creator->id,
                    'name' => $task->creator->name,
                    'email' => $task->creator->email,
                ] : null,
                'llm_chat' => $task->llmChat ? [
                    'id' => $task->llmChat->id,
                    'messages' => $task->llmChat->messages,
                    'context_fill' => $task->llmChat->context_fill,
                    'created_at' => $task->llmChat->created_at,
                    'updated_at' => $task->llmChat->updated_at,
                ] : null,
                'techplane' => $task->techplane,
                'attachedPageVersions' => $task->pageVersions->map(function ($pv) {
                    return [
                        'id' => $pv->id,
                        'title' => $pv->title,
                        'version' => $this->computeVersionNumber($pv),
                    ];
                }),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified task.
     */
    public function edit(VersionDiffTask $task): Response
    {

        // Загружаем связанные данные
        $task->load([
            'pageVersion.page.creator',
            'pageVersion.previousVersion',
            'creator',
            'llmChat',
            'pageVersions.page'
        ]);

        $pageVersion = $task->pageVersion;
        $page = $pageVersion?->page;

        return Inertia::render('tasks/Edit', [
            'task' => [
                'id' => $task->id,
                'content' => $task->content,
                'generation_status' => $task->generationStatus(),
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
                'edited_at' => $task->edited_at,
                'pageVersion' => $pageVersion ? [
                    'id' => $pageVersion->id,
                    'title' => $pageVersion->title,
                    'content' => $pageVersion->content,
                    'created_at' => $pageVersion->created_at,
                    'page' => $page ? [
                        'id' => $page->id,
                        'title' => $page->title,
                        'content' => $page->content,
                        'created_at' => $page->created_at,
                        'creator' => $page->creator ? [
                            'id' => $page->creator->id,
                            'name' => $page->creator->name,
                            'email' => $page->creator->email,
                        ] : null,
                    ] : null,
                    'previousVersion' => $pageVersion->previousVersion ? [
                        'id' => $pageVersion->previousVersion->id,
                        'title' => $pageVersion->previousVersion->title,
                        'content' => $pageVersion->previousVersion->content,
                        'created_at' => $pageVersion->previousVersion->created_at,
                    ] : null,
                ] : null,
                'creator' => [
                    'id' => $task->creator->id,
                    'name' => $task->creator->name,
                    'email' => $task->creator->email,
                ],
                'llm_chat' => $task->llmChat ? [
                    'id' => $task->llmChat->id,
                    'messages' => $task->llmChat->messages,
                    'context_fill' => $task->llmChat->context_fill,
                    'created_at' => $task->llmChat->created_at,
                    'updated_at' => $task->llmChat->updated_at,
                ] : null,
                'attachedPageVersions' => $task->pageVersions->map(function ($pv) {
                    return [
                        'id' => $pv->id,
                        'title' => $pv->title,
                        'version' => $this->computeVersionNumber($pv),
                    ];
                }),
            ]
        ]);

    }

    private function computeVersionNumber(PageVersion $pageVersion): int
    {
        $num = 1;
        $current = $pageVersion;
        while ($current->previous_version_id) {
            $current = $current->previousVersion;
            $num++;
        }
        return $num;
    }

    /**
     * Update the specified task.
     */
    public function update(
        TaskUpdateRequest       $request,
        VersionDiffTask         $task,
        LLMChatFactoryInterface $chatFactory,
        PromptProviderFactory   $promptProviderFactory,
    ): RedirectResponse
    {
        // Проверяем права доступа
        if ($task->created_by !== Auth::id()) {
            abort(403, 'У вас нет прав для редактирования этой задачи');
        }

        // Валидация входящих данных
        $validated = $request->validated();

        $updates = [
            'content' => $validated['content'],
            'generation_status' => GenerationStatus::COMPLETED->value,
        ];

        // Применяем изменения привязок, если переданы
        $attachmentsAdd = collect($request->input('attachments_add', []))->map(fn($v) => (int)$v)->all();
        $attachmentsRemove = collect($request->input('attachments_remove', []))->map(fn($v) => (int)$v)->all();

        if (!empty($attachmentsAdd)) {
            $task->pageVersions()->syncWithoutDetaching($attachmentsAdd);
        }
        if (!empty($attachmentsRemove)) {
            $task->pageVersions()->detach($attachmentsRemove);
        }

        if ($request->isResetChat()) {
            $promptProvider = $promptProviderFactory->createProjectPromptService($task->project_id);
            $updates['llm_chat_id'] = $chatFactory->createChatForUpdatedTask($promptProvider, $task)->id;
        } else {
            //restore conversation from llm_chat and append updated content
        }

        $task->update($updates);

        $task->markAsEdited();
        $task->clearTechplane();

        return redirect()->route('tasks.show', $task)
            ->with('success', 'Задача успешно обновлена');
    }

    /**
     * Check the generation status of a task.
     */
    public function checkGenerationStatus(VersionDiffTask $task): JsonResponse
    {
        // Подгружаем чат, если он существует, чтобы обновлять сообщения на клиенте
        $task->loadMissing('llmChat');

        return response()->json([
            'status' => $task->generationStatus(),
            'content' => $task->content,
            'updated_at' => $task->updated_at,
            'chat' => $task->llmChat ? [
                'id' => $task->llmChat->id,
                'messages' => $task->llmChat->messages,
                'context_fill' => $task->llmChat->context_fill,
            ] : null,
        ]);
    }

    /**
     * Restart generation of task description.
     */
    public function restartGeneration(VersionDiffTask $task): JsonResponse
    {
        // Проверить, что генерация не выполняется в данный момент
        if ($task->generation_status === VersionDiffTask::STATUS_GENERATING) {
            return response()->json([
                'success' => false,
                'message' => 'Генерация уже выполняется'
            ], 400);
        }

        // Сбросить статус и контент
        $task->update([
            'generation_status' => VersionDiffTask::STATUS_PENDING,
            'content' => null,
            'llm_chat_id' => null
        ]);

        // Запустить новую генерацию
        GenerateTaskDescriptionJob::dispatch($task->id);

        return response()->json([
            'success' => true,
            'message' => 'Генерация перезапущена'
        ]);
    }

    /**
     * Создать техплан для задачи
     */
    public function createTechplane(VersionDiffTask $task): RedirectResponse
    {
        $techplane = Techplane::create([
            'task_id' => $task->id,
            'created_by' => Auth::id(),
            'generation_status' => Techplane::STATUS_PENDING,
        ]);

        // Запустить фоновую генерацию
        GenerateTechplaneJob::dispatch($techplane->id);

        return redirect()->route('techplanes.show', $techplane)
            ->with('success', 'Техплан создан, генерация запущена');
    }

    /**
     * Отправить сообщение в чат задачи
     */
    public function sendMessage(
        SendTaskMessageRequest             $request,
        VersionDiffTask                    $task,
        ConversationFactory                $conversationFactory,
        AgentResultHandlerFactoryInterface $handlerFactory,
        AgentTaskManagerInterface          $agentTaskManager
    ): JsonResponse
    {
        if ($task->generation_status !== VersionDiffTask::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'Запущена генерация'
            ], 403);
        }

        try {
            $dto = SendMessageDTO::fromRequest($request, $task);

            $success = DB::transaction(function () use ($dto, $task, $conversationFactory, $handlerFactory, $agentTaskManager) {
                // Получаем или создаем чат
                $chat = $task->llmChat;
                if (!$chat) {
                    $chat = LLMChat::create(['messages' => []]);
                }

                // Используем фабрику для создания чата из существующих сообщений
                $conversation = $conversationFactory->fromMessages($chat->messages ?? []);

                // Добавляем новое пользовательское сообщение
                $userMessage = new UserMessage($dto->message);
                $conversation->addMessage($userMessage);

                // Сохраняем обновленный чат
                $chatUpdated = $chat->update(['messages' => $conversation->serialize()]);

                if ($chatUpdated) {
                    // Создаем новую задачу для агента с обновленным чатом
                    $handler = $handlerFactory->createVersionDiffResultHandler($task);

                    $projectId = $task->project_id ?? $task->pageVersion?->page?->project_id;
                    abort_if(!$projectId, 400, 'Project is required for agent task');

                    $agentTaskManager->createTask(
                        $handler,
                        $dto->userId,
                        $projectId,
                        $chat->id,
                        false,
                        AgentTaskType::TEXT
                    );

                    $task->update([
                        'llm_chat_id' => $chat->id
                    ]);
                }

                return $chatUpdated;
            });

            if ($success) {
                // Обновляем задачу с актуальными данными чата
                $task->load('llmChat');

                return response()->json([
                    'success' => true,
                    'message' => 'Сообщение отправлено и передано агенту на обработку',
                    'chat' => [
                        'id' => $task->llmChat->id,
                        'messages' => $task->llmChat->messages,
                        'context_fill' => $task->llmChat->context_fill,
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отправке сообщения'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Failed to send task message', [
                'task_id' => $task->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Внутренняя ошибка сервера'
            ], 500);
        }
    }

    /**
     * Remove the specified task from storage.
     */
    public function destroy(Project $project, ?VersionDiffTask $projectTask = null): RedirectResponse
    {

        // Проверяем права доступа
        if ($projectTask->created_by !== Auth::id()) {
            abort(403, 'У вас нет прав для удаления этой задачи');
        }

        if ($projectTask->project_id !== $project->id) {
            abort(404);
        }

        $projectTask->delete();

        return redirect()->back()
            ->with('success', 'Задача успешно удалена');
    }

    public function store(
        \App\Http\Requests\TaskStoreRequest $request,
        Project                             $project,
        PromptProviderFactory               $promptProviderFactory,
    ): RedirectResponse
    {
        if (!$project->canAccess(Auth::user())) {
            abort(403);
        }

        $validated = $request->validated();
        $description = $validated['description'] ?? null;

        $promptProvider = $promptProviderFactory->createProjectPromptService($project->id);

        $chat = new Chat();
        $chat->addMessage(new SystemMessage($promptProvider->getDescriptionGeneratorRole()));
        if ($description) {
            $chat->addMessage(new UserMessage("[TASK_DESCRIPTION]\n" . trim($description)));
        }
        $chat = LLMChat::create(['messages' => $chat->serialize()]);

        $task = VersionDiffTask::create([
            'project_id' => $project->id,
            'created_by' => Auth::id(),
            'content' => $description ?? '',
            // Задача создается вручную, генерация не требуется
            'generation_status' => VersionDiffTask::STATUS_COMPLETED,
            'page_version_id' => null,
            'llm_chat_id' => $chat->id,
            'edited_at' => null,
        ]);

        return redirect()->route('tasks.edit', $task);
    }

    public function stopGenerating(
        VersionDiffTask           $task,
        AgentTaskManagerInterface $agentTaskManager
    )
    {
        $agentTask = AgentTask::find(['chat_id' => $task->llmChat->id])->firstOrFail();

        $agentTaskManager->stopTask($agentTask->id);

        return response()->json(['chat_id' => $task->llmChat->id, 'message' => 'Задача успешно остановлена', 'success' => true]);
    }
}
