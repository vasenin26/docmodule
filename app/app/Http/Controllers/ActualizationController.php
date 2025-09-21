<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartPageActualizationRequest;
use App\Http\Requests\SendActualizationMessageRequest;
use App\Models\Actualization;
use App\Models\LLMChat;
use App\Models\Page;
use App\Models\PageVersion;
use App\Services\ActualizationService;
use App\Common\DTO\ActualizationDTO;
use App\Common\DTO\PageDataDTO;
use App\Common\DTO\SendActualizationMessageDTO;
use App\Common\Enums\AgentTaskType;
use App\Interfaces\AgentTaskManagerInterface;
use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Vasenin26\Conversation\Factory\ConversationFactory;
use Vasenin26\Conversation\Messages\UserMessage;

class ActualizationController extends Controller
{
    public function __construct(
        private ActualizationService $actualizationService
    ) {
    }

    /**
     * Запустить актуализацию для конкретного черновика
     */
    public function storeForDraft(StartPageActualizationRequest $request, PageVersion $draft): JsonResponse
    {
        try {
            $actualization = $this->actualizationService->initiate($draft, $request->user());
            $actualizationDTO = ActualizationDTO::fromModel($actualization);

            return response()->json([
                'success' => true,
                'message' => 'Актуализация успешно запущена для черновика',
                'data' => $actualizationDTO->toArray()
            ]);

        } catch (\RuntimeException $e) {
            Log::error('Actualization runtime error for draft', [
                'message' => $e->getMessage(),
                'draft_id' => $draft->id,
                'page_id' => $draft->page_id,
                'user_id' => $request->user()->id ?? 'no user',
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Actualization error for draft', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'draft_id' => $draft->id,
                'page_id' => $draft->page_id,
                'user_id' => $request->user()->id ?? 'no user',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при запуске актуализации',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Получить статус актуализации для страницы
     */
    public function status(Request $request, Page $page): JsonResponse
    {
        $status = $this->actualizationService->getStatus($page);

        return response()->json([
            'success' => true,
            'data' => $status
        ]);
    }

    /**
     * Показать результаты актуализации
     */
    public function show(Request $request, Actualization $actualization): Response
    {
        $details = $this->actualizationService->getDetails($actualization);

        return Inertia::render('pages/ActualizationShow', [
            'actualization' => $details,
            'page' => $details['page'],
            'chat' => $details['chat'],
        ]);
    }

    /**
     * Отменить актуализацию
     */
    public function cancel(Request $request, Actualization $actualization): JsonResponse
    {
        // Проверка прав доступа
        $this->authorize('update', $actualization->page);

        try {
            $this->actualizationService->cancel($actualization);

            return response()->json([
                'success' => true,
                'message' => 'Актуализация отменена',
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при отмене актуализации',
            ], 500);
        }
    }

    /**
     * Получить список актуализаций для страницы
     */
    public function index(Request $request, Page $page): JsonResponse
    {
        $actualizations = $page->actualizations()
            ->with(['createdBy'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($actualization) {
                return [
                    'id' => $actualization->id,
                    'status' => $actualization->status,
                    'created_at' => $actualization->created_at,
                    'updated_at' => $actualization->updated_at,
                    'created_by' => $actualization->createdBy->name ?? 'Unknown',
                    'has_chat' => !is_null($actualization->llm_chat_id),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $actualizations
        ]);
    }

    /**
     * Получить статус актуализации с данными чата
     */
    public function getStatusWithChat(Request $request, Actualization $actualization): JsonResponse
    {
        $this->authorize('view', $actualization->page);

        $data = $this->actualizationService->getStatusWithChat($actualization);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Отправить сообщение в чат актуализации
     */
    public function sendMessage(
        SendActualizationMessageRequest $request,
        Actualization $actualization,
        ConversationFactory $conversationFactory,
        AgentResultHandlerFactoryInterface $handlerFactory,
        AgentTaskManagerInterface $agentTaskManager
    ): JsonResponse {
        $this->authorize('update', $actualization->page);

        if (!in_array($actualization->status, [Actualization::STATUS_PENDING, Actualization::STATUS_PROCESSING])) {
            return response()->json([
                'success' => false,
                'message' => 'Актуализация завершена, отправка сообщений недоступна'
            ], 403);
        }

        try {
            $dto = SendActualizationMessageDTO::fromRequest($request, $actualization);

            $success = DB::transaction(function () use ($dto, $actualization, $conversationFactory, $handlerFactory, $agentTaskManager) {
                // Получаем или создаем чат
                $chat = $actualization->llmChat;
                if (!$chat) {
                    $chat = LLMChat::create(['messages' => []]);
                    $actualization->update(['llm_chat_id' => $chat->id]);
                }

                // Используем фабрику для создания чата из существующих сообщений
                $conversation = $conversationFactory->fromMessages($chat->messages ?? []);

                // Добавляем новое пользовательское сообщение
                $userMessage = new UserMessage($dto->message);
                $conversation->addMessage($userMessage);

                // Сохраняем обновленный чат
                $chatUpdated = $chat->update(['messages' => $conversation->serialize()]);

                if ($chatUpdated) {
                    $handler = $handlerFactory->createActualizationResultHandler($actualization);

                    $agentTaskManager->createTask(
                        $handler,
                        $dto->userId,
                        $actualization->page->project_id,
                        $chat->id,
                        false,
                        AgentTaskType::TEXT
                    );
                }

                return $chatUpdated;
            });

            if ($success) {
                $actualization->load('llmChat');

                return response()->json([
                    'success' => true,
                    'message' => 'Сообщение отправлено и передано агенту на обработку',
                    'chat' => [
                        'id' => $actualization->llmChat->id,
                        'messages' => $actualization->llmChat->messages
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отправке сообщения'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Failed to send actualization message', [
                'actualization_id' => $actualization->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Внутренняя ошибка сервера'
            ], 500);
        }
    }
}
