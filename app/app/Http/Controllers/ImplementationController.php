<?php

namespace App\Http\Controllers;

use App\Common\DTO\SendImplementationMessageDTO;
use App\Common\Enums\GenerationStatus;
use App\Http\Requests\CreateImplementationRequest;
use App\Http\Requests\SendImplementationMessageRequest;
use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use App\Interfaces\AgentTaskManagerInterface;
use App\Jobs\ProcessImplementationJob;
use App\Models\Implementation;
use App\Models\LLMChat;
use Vasenin26\Conversation\Factory\ConversationFactory;
use Vasenin26\Conversation\Messages\UserMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\AgentTask;

class ImplementationController extends Controller
{
    public function show(Implementation $implementation): Response
    {
        $implementation->load([
            'techplane.task.pageVersion.page.project',
            'creator',
            'llmChat'
        ]);

        return Inertia::render('implementation/Show', [
            'implementation' => [
                'id' => $implementation->id,
                'content' => $implementation->content,
                'status' => $implementation->status->value,
                'actual_status' => $implementation->actualStatus()->value,
                'created_at' => $implementation->created_at,
                'updated_at' => $implementation->updated_at,
                'techplane' => [
                    'id' => $implementation->techplane->id,
                    'task' => [
                        'id' => $implementation->techplane->task->id,
                        'title' => $implementation->techplane->task->title,
                    ],
                ],
                'creator' => $implementation->creator,
                'llm_chat' => $implementation->llmChat?->toApiArray(),
            ],
            'project_id' => $implementation->techplane->task->project_id,
        ]);
    }

    public function store(CreateImplementationRequest $request): RedirectResponse
    {
        $techplane = $request->route('techplane');

        // Создаем реализацию
        $implementation = $techplane->createImplementation(Auth::id());

        // Запускаем обработку в фоне
        ProcessImplementationJob::dispatch($implementation->id);

        return redirect()->route('implementations.show', $implementation)
            ->with('success', 'Реализация создана, обработка запущена');
    }

    public function checkStatus(Implementation $implementation): JsonResponse
    {
        $implementation->load('llmChat');
        $actualStatus = $implementation->actualStatus();

        return response()->json([
            'status' => $actualStatus->value,
            'content' => $implementation->content,
            'updated_at' => $implementation->updated_at,
            'chat' => $implementation->llmChat?->toApiArray(),
        ]);
    }

    public function sendMessage(
        SendImplementationMessageRequest $request,
        Implementation $implementation,
        ConversationFactory $conversationFactory,
        AgentResultHandlerFactoryInterface $handlerFactory,
        AgentTaskManagerInterface $agentTaskManager
    ): JsonResponse {
        // Проверяем актуальный статус реализации
        if(!$implementation->actualStatus()->isFinal()) {
            return response()->json([
                'success' => false,
                'message' => 'Реализация не завершена'
            ], 403);
        }

        try {
            $dto = SendImplementationMessageDTO::fromRequest($request, $implementation);

            $success = DB::transaction(function () use ($dto, $implementation, $conversationFactory, $handlerFactory, $agentTaskManager) {
                // Получаем или создаем чат
                $chat = $implementation->llmChat;
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
                    // Создаем обработчик и задачу агента
                    $handler = $handlerFactory->createImplementationResultHandler($implementation);

                    $agentTaskManager->createTask(
                        $handler,
                        $dto->userId,
                        $implementation->techplane->task->project_id,
                        $chat->id,
                        false,
                        \App\Common\Enums\AgentTaskType::CODE
                    );

                    return true;
                }

                return false;
            });

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Сообщение отправлено'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отправке сообщения'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Error sending implementation message', [
                'implementation_id' => $implementation->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при отправке сообщения'
            ], 500);
        }
    }

    /**
     * Остановить генерацию агентских задач для реализации
     */
    public function stopGenerating(Implementation $implementation, AgentTaskManagerInterface $agentTaskManager): JsonResponse
    {
        $stoppedTasks = AgentTask::stopGeneratingForChat((int)$implementation->chat_id, $agentTaskManager);

        return response()->json([
            'implementation_id' => $implementation->id,
            'agent_task_id' => $stoppedTasks,
            'chat_id' => $implementation->chat_id,
            'message' => 'Генерация реализации успешно остановлена',
            'success' => true
        ]);
    }
}
