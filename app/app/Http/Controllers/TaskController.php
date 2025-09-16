<?php

namespace App\Http\Controllers;

use App\Common\Enums\AgentTaskType;
use App\Jobs\GenerateTaskDescriptionJob;
use App\Jobs\GenerateTechplaneJob;
use App\Models\VersionDiffTask;
use App\Models\Techplane;
use App\Models\LLMChat;
use App\Models\Project;
use App\Http\Requests\TaskUpdateRequest;
use App\Http\Requests\SendTaskMessageRequest;
use App\Common\DTO\SendMessageDTO;
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
     * Display the specified task.
     */
    public function show(VersionDiffTask $task): Response
    {
        $task->load(['pageVersion.page', 'techplane', 'llmChat', 'creator']);

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
                    'created_at' => $task->llmChat->created_at,
                    'updated_at' => $task->llmChat->updated_at,
                ] : null,
                'techplane' => $task->techplane,
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
            'llmChat'
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
                    'created_at' => $task->llmChat->created_at,
                    'updated_at' => $task->llmChat->updated_at,
                ] : null,
            ]
        ]);
    }

    /**
     * Update the specified task.
     */
    public function update(TaskUpdateRequest $request, VersionDiffTask $task): RedirectResponse
    {
        // Проверяем права доступа
        if ($task->created_by !== Auth::id()) {
            abort(403, 'У вас нет прав для редактирования этой задачи');
        }

        // Валидация входящих данных
        $validated = $request->validated();

        // Обновляем содержимое задачи
        $task->update([
            'content' => $validated['content'],
        ]);

        // Отмечаем задачу как отредактированную
        $task->markAsEdited();

        // Очищаем связанный техплан
        $task->clearTechplane();

        // Гарантируем наличие чата и добавляем сообщение с новым контентом
        $chat = $task->llmChat;
        if (!$chat) {
            $chat = LLMChat::create(['messages' => []]);
            $task->update(['llm_chat_id' => $chat->id]);
        }

        // Обновляем историю сообщений, добавляя новый контент как пользовательское сообщение
        $conversation = (new ConversationFactory())->fromMessages($chat->messages ?? []);
        $conversation->addMessage(new UserMessage($validated['content']));
        $chat->update(['messages' => $conversation->serialize()]);

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
        SendTaskMessageRequest $request,
        VersionDiffTask $task,
        ConversationFactory $conversationFactory,
        AgentResultHandlerFactoryInterface $handlerFactory,
        AgentTaskManagerInterface $agentTaskManager
    ): JsonResponse
    {
        if($task->generation_status !== VersionDiffTask::STATUS_COMPLETED) {
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
                        'messages' => $task->llmChat->messages
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

    public function store(Project $project, Request $request): RedirectResponse
    {
        if (!$project->canAccess(Auth::user())) {
            abort(403);
        }

        $chat = LLMChat::create(['messages' => []]);

        $task = VersionDiffTask::create([
            'project_id' => $project->id,
            'created_by' => Auth::id(),
            'content' => '',
            'generation_status' => VersionDiffTask::STATUS_PENDING,
            'page_version_id' => null,
            'llm_chat_id' => $chat->id,
            'edited_at' => null,
        ]);

        return redirect()->route('tasks.edit', $task);
    }
}
