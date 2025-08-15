<?php

namespace App\Http\Controllers;

use App\Models\LLMChat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Отображение страницы дашборда
     *
     * @return Response
     */
    public function index(): Response
    {
        $tokenStatistics = $this->getTokenStatistics();

        return Inertia::render('Dashboard', [
            'token_statistics' => $tokenStatistics
        ]);
    }

    /**
     * Получение статистики токенов
     *
     * @return array
     */
    public function getTokenStatistics(): array
    {
        // Получаем агрегированную статистику из базы данных
        $stats = LLMChat::selectRaw('
            COALESCE(SUM(prompt_tokens), 0) as prompt_tokens,
            COALESCE(SUM(completion_tokens), 0) as completion_tokens,
            COALESCE(SUM(total_tokens), 0) as total_tokens
        ')->first();

        return [
            'prompt_tokens' => (int) $stats->prompt_tokens,
            'completion_tokens' => (int) $stats->completion_tokens,
            'total_tokens' => (int) $stats->total_tokens,
        ];
    }

    /**
     * API endpoint для получения статистики токенов
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiTokenStatistics(Request $request)
    {
        $statistics = $this->getTokenStatistics();

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }
}
