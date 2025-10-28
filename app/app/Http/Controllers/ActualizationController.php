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

        if ($version->hasActualization()) {
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

    public function restart(Request $request, Actualization $actualization, AgentTaskManagerInterface $agentTaskManager): JsonResponse
    {
        if (!$actualization->page->project->canAccess(Auth::user())) {
            abort(403);
        }

        try {
            $this->actualizationService->restart($actualization, $request->user(), $agentTaskManager);
            $actualizationDTO = ActualizationDTO::fromModel($actualization);

            return response()->json([
                'success' => true,
                'message' => 'Актуализация успешно запущена для черновика',
                'data' => $actualizationDTO->toArray()
            ]);

        } catch (\RuntimeException $e) {
            Log::error('Actualization runtime error for draft', [
                'message' => $e->getMessage(),
                'actualisation_id' => $actualization->id,
                'user_id' => $request->user()->id ?? 'no user',
            ]);

            return response()->json([
                'success' => false,
                'actualisation_id' => $actualization->id,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Actualization error for draft', [
                'actualisation_id' => $actualization->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id ?? 'no user',
            ]);

            return response()->json([
                'success' => false,
                'actualisation_id' => $actualization->id,
                'message' => 'Произошла ошибка при запуске актуализации',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
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
}
