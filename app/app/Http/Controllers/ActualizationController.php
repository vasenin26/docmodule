<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActualizationRequest;
use App\Models\Actualization;
use App\Models\Page;
use App\Services\ActualizationService;
use App\Common\DTO\PageDataDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ActualizationController extends Controller
{
    public function __construct(
        private ActualizationService $actualizationService
    ) {
    }

    /**
     * Инициировать процесс актуализации страницы
     */
    public function store(StoreActualizationRequest $request, Page $page): JsonResponse
    {
        try {
            $actualization = $this->actualizationService->initiate($page, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Актуализация успешно запущена',
                'data' => [
                    'actualization_id' => $actualization->id,
                    'status' => $actualization->status,
                    'page_id' => $page->id,
                ]
            ]);

        } catch (\RuntimeException $e) {
            Log::error('Actualization runtime error', [
                'message' => $e->getMessage(),
                'page_id' => $page->id,
                'user_id' => $request->user()->id ?? 'no user',
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Actualization error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'page_id' => $page->id,
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
}
