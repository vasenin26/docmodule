<?php

namespace App\Http\Controllers;

use App\Common\DTO\Actualization\ActualizationDTO;
use App\Common\DTO\Actualization\SendActualizationMessageDTO;
use App\Common\Enums\AgentTaskType;
use App\Http\Requests\SendActualizationMessageRequest;
use App\Http\Requests\StartPageActualizationRequest;
use App\Interfaces\AgentTaskManagerInterface;
use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use App\Models\Actualization;
use App\Models\AgentTask;
use App\Models\LLMChat;
use App\Models\Page;
use App\Models\PageVersion;
use App\Services\ActualizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Vasenin26\Conversation\Factory\ConversationFactory;
use Vasenin26\Conversation\Messages\UserMessage;

class ActualizationController extends Controller
{
    public function __construct(
        private ActualizationService $actualizationService
    )
    {
    }

    /**
     * Запустить актуализацию для конкретного черновика
     */
    public function start(StartPageActualizationRequest $request, PageVersion $version): JsonResponse
    {
        if (!$version->page->project->canAccess(Auth::user())) {
            abort(403);
        }

        if($version->hasActualization()) {
            return response()->json([
                'success' => true,
                'message' => 'version_have_actualisation',
                'data' => ActualizationDTO::fromModel($version->actualisation)->toArray()
            ]);
        }

        if (!$version->is_draft) {
            $version = $version->createNewVersion();
        }

        try {
            $actualization = $this->actualizationService->initiate($version, $request->user());
            $actualizationDTO = ActualizationDTO::fromModel($actualization);

            return response()->json([
                'success' => true,
                'message' => 'Актуализация успешно запущена для черновика',
                'data' => $actualizationDTO->toArray()
            ]);

        } catch (\RuntimeException $e) {
            Log::error('Actualization runtime error for draft', [
                'message' => $e->getMessage(),
                'draft_id' => $version->id,
                'page_id' => $version->page_id,
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
                'draft_id' => $version->id,
                'page_id' => $version->page_id,
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
        return Inertia::render('pages/ActualizationShow', [
            'project_id' => $actualization->page->project_id,
            'actualization' => $actualization,
            'version' => $actualization->pageVersion,
            'chat' => $actualization->llmChat,
        ]);
    }

    /**
     * Отменить актуализацию
     */
    public function cancel(Request $request, Actualization $actualization): JsonResponse
    {
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
        SendActualizationMessageRequest    $request,
        Actualization                      $actualization,
        ConversationFactory                $conversationFactory,
        AgentResultHandlerFactoryInterface $handlerFactory,
        AgentTaskManagerInterface          $agentTaskManager
    ): JsonResponse
    {
        try {
            $dto = SendActualizationMessageDTO::fromRequest($request, $actualization);

            if ($actualization->isGenerating()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Есть запущенная актуализация'
                ], 500);
            }

            // Разрешаем корректировку после завершения: если статус completed/failed — переводим в pending
            if (in_array($actualization->status, [Actualization::STATUS_COMPLETED, Actualization::STATUS_FAILED])) {
                $actualization->update(['status' => Actualization::STATUS_PENDING]);
            }

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
                        AgentTaskType::ACTUALIZATION
                    );
                }

                return $chatUpdated;
            });

            if ($success) {
                $actualization->load('llmChat');

                return response()->json([
                    'success' => true,
                    'message' => 'Сообщение отправлено и передано агенту на обработку',
                    'chat' => $actualization->llmChat?->toApiArray()
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

    /**
     * Остановить генерацию агентских задач для актуализации
     */
    public function stopGenerating(Actualization $actualization, AgentTaskManagerInterface $agentTaskManager): JsonResponse
    {
        $stoppedTasks = AgentTask::stopGeneratingForChat((int)$actualization->llm_chat_id, $agentTaskManager);

        $actualization->update(['status' => Actualization::STATUS_COMPLETED]);

        return response()->json([
            'actualization_id' => $actualization->id,
            'agent_task_id' => $stoppedTasks,
            'chat_id' => $actualization->llm_chat_id,
            'message' => 'Генерация актуализации успешно остановлена',
            'success' => true
        ]);
    }
}
