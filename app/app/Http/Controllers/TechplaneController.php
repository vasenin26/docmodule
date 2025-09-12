<?php

namespace App\Http\Controllers;

use App\Common\DTO\SendTechplaneMessageDTO;
use App\Common\Enums\AgentTaskType;
use Vasenin26\Conversation\Factory\ConversationFactory;
use App\Http\Requests\CreateImplementationRequest;
use App\Http\Requests\SendTechplaneMessageRequest;
use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use App\Interfaces\AgentTaskManagerInterface;
use App\Jobs\GenerateTechplaneJob;
use App\Jobs\ProcessImplementationJob;
use App\Models\AgentTask;
use App\Models\LLMChat;
use App\Models\Techplane;
use Vasenin26\Conversation\Messages\UserMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class TechplaneController extends Controller
{
    public function show(Techplane $techplane): Response
    {
        $techplane->load(['task.pageVersion.page', 'creator', 'llmChat']);

        return Inertia::render('techplane/Show', [
            'techplane' => [
                'id' => $techplane->id,
                'content' => $techplane->content,
                'generation_status' => $techplane->generationStatus(),
                'created_at' => $techplane->created_at,
                'updated_at' => $techplane->updated_at,
                'task' => [
                    'id' => $techplane->task->id,
                    'pageVersion' => [
                        'id' => $techplane->task->pageVersion->id,
                        'title' => $techplane->task->pageVersion->title,
                        'page' => [
                            'id' => $techplane->task->pageVersion->page->id,
                            'title' => $techplane->task->pageVersion->page->title,
                        ],
                    ],
                ],
                'creator' => $techplane->creator ? [
                    'id' => $techplane->creator->id,
                    'name' => $techplane->creator->name,
                    'email' => $techplane->creator->email,
                ] : null,
                'llm_chat' => $techplane->llmChat ? [
                    'id' => $techplane->llmChat->id,
                    'messages' => $techplane->llmChat->messages,
                    'created_at' => $techplane->llmChat->created_at,
                    'updated_at' => $techplane->llmChat->updated_at,
                ] : null,
            ],
        ]);
    }

    /**
     * Restart generation of techplane.
     */
    public function restartGeneration(Techplane $techplane): JsonResponse
    {
        if ($techplane->generationStatus() === Techplane::STATUS_GENERATING) {
            return response()->json([
                'success' => false,
                'message' => 'Генерация уже выполняется'
            ], 400);
        }

        $techplane->update([
            'generation_status' => Techplane::STATUS_PENDING,
            'content' => null,
            'chat_id' => null
        ]);

        GenerateTechplaneJob::dispatch($techplane->id);

        return response()->json([
            'success' => true,
            'message' => 'Генерация перезапущена'
        ]);
    }

    /**
     * Check the generation status of a techplane.
     */
    public function checkGenerationStatus(Techplane $techplane): JsonResponse
    {
        // Подгружаем чат, если он существует, чтобы обновлять сообщения на клиенте
        $techplane->loadMissing('llmChat');

        return response()->json([
            'status' => $techplane->generationStatus(),
            'content' => $techplane->content,
            'updated_at' => $techplane->updated_at,
            'chat' => $techplane->llmChat ? [
                'id' => $techplane->llmChat->id,
                'messages' => $techplane->llmChat->messages,
            ] : null,
        ]);
    }

    /**
     * Отправить сообщение в чат техплана
     */
    public function sendMessage(
        SendTechplaneMessageRequest $request,
        Techplane $techplane,
        ConversationFactory $conversationFactory,
        AgentResultHandlerFactoryInterface $handlerFactory,
        AgentTaskManagerInterface $agentTaskManager
    ): JsonResponse {
        if($techplane->generation_status !== Techplane::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'Запущена генерация'
            ], 403);
        }

        try {
            $dto = SendTechplaneMessageDTO::fromRequest($request, $techplane);

            $success = DB::transaction(function () use ($dto, $techplane, $conversationFactory, $handlerFactory, $agentTaskManager) {
                // Получаем или создаем чат
                $chat = $techplane->llmChat;
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
                    $handler = $handlerFactory->createTechplaneResultHandler($techplane);

                    $agentTaskManager->createTask(
                        $handler,
                        $dto->userId,
                        $techplane->task->pageVersion->page->project_id,
                        $chat->id,
                        false,
                        AgentTaskType::TEXT
                    );

                    $techplane->update([
                        'chat_id' => $chat->id
                    ]);
                }

                return $chatUpdated;
            });

            if ($success) {
                // Обновляем техплан с актуальными данными чата
                $techplane->load('llmChat');

                return response()->json([
                    'success' => true,
                    'message' => 'Сообщение отправлено и передано агенту на обработку',
                    'chat' => [
                        'id' => $techplane->llmChat->id,
                        'messages' => $techplane->llmChat->messages
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отправке сообщения'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Failed to send techplane message', [
                'techplane_id' => $techplane->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Внутренняя ошибка сервера'
            ], 500);
        }
    }

    public function execute(CreateImplementationRequest $request, Techplane $techplane): JsonResponse|RedirectResponse
    {
        // Создаем реализацию
        $implementation = $techplane->createImplementation(Auth::id());
        
        // Запускаем обработку в фоне
        ProcessImplementationJob::dispatch($implementation->id);
        
        // Если это AJAX запрос, возвращаем JSON
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Реализация создана, обработка запущена',
                'redirect_url' => route('implementations.show', $implementation)
            ]);
        }
        
        // Иначе возвращаем редирект
        return redirect()->route('implementations.show', $implementation)
            ->with('success', 'Реализация создана, обработка запущена');
    }
}
