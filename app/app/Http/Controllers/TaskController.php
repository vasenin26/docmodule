<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateTaskDescriptionJob;
use App\Jobs\GenerateTechplaneJob;
use App\Models\PageDiffDescription;
use App\Models\Techplane;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
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
     * Display the specified task.
     */
    public function show(PageDiffDescription $task): Response
    {
        // Загружаем связанные данные включая техплан
        $task->load([
            'page.creator', 
            'page.previousVersion', 
            'creator', 
            'llmChat',
            'techplane.creator'
        ]);

        return Inertia::render('tasks/Show', [
            'task' => [
                'id' => $task->id,
                'content' => $task->content,
                'generation_status' => $task->generation_status,
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
                'page' => [
                    'id' => $task->page->id,
                    'title' => $task->page->title,
                    'content' => $task->page->content,
                    'created_at' => $task->page->created_at,
                    'creator' => [
                        'id' => $task->page->creator->id,
                        'name' => $task->page->creator->name,
                        'email' => $task->page->creator->email,
                    ],
                    'previous_version' => $task->page->previousVersion ? [
                        'id' => $task->page->previousVersion->id,
                        'title' => $task->page->previousVersion->title,
                        'content' => $task->page->previousVersion->content,
                    ] : null,
                ],
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
                'techplane' => $task->techplane ? [
                    'id' => $task->techplane->id,
                    'content' => $task->techplane->content,
                    'generation_status' => $task->techplane->generation_status,
                    'created_at' => $task->techplane->created_at,
                    'creator' => [
                        'id' => $task->techplane->creator->id,
                        'name' => $task->techplane->creator->name,
                    ],
                ] : null,
            ]
        ]);
    }

    /**
     * Check the generation status of a task.
     */
    public function checkGenerationStatus(PageDiffDescription $task): JsonResponse
    {
        return response()->json([
            'status' => $task->generation_status,
            'content' => $task->content,
            'updated_at' => $task->updated_at,
        ]);
    }

    /**
     * Restart generation of task description.
     */
    public function restartGeneration(PageDiffDescription $task): JsonResponse
    {
        // Проверить, что генерация не выполняется в данный момент
        if ($task->generation_status === PageDiffDescription::STATUS_GENERATING) {
            return response()->json([
                'success' => false,
                'message' => 'Генерация уже выполняется'
            ], 400);
        }

        // Сбросить статус и контент
        $task->update([
            'generation_status' => PageDiffDescription::STATUS_PENDING,
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
    public function createTechplane(PageDiffDescription $task): RedirectResponse
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
}
