<?php

namespace App\Http\Controllers;

use App\Models\PageDiffDescription;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
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
        // Загружаем связанные данные
        $task->load(['page.creator', 'page.previousVersion', 'creator']);

        return Inertia::render('tasks/Show', [
            'task' => [
                'id' => $task->id,
                'content' => $task->content,
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
            ]
        ]);
    }
}
