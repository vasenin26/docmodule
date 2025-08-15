<?php

namespace App\Http\Controllers;

use App\Models\Techplane;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TechplaneController extends Controller
{
    public function show(Techplane $techplane): Response
    {
        $techplane->load(['task.page.creator', 'creator', 'llmChat']);

        return Inertia::render('techplane/Show', [
            'techplane' => [
                'id' => $techplane->id,
                'content' => $techplane->content,
                'generation_status' => $techplane->generation_status,
                'created_at' => $techplane->created_at,
                'updated_at' => $techplane->updated_at,
                'task' => [
                    'id' => $techplane->task->id,
                    'content' => $techplane->task->content,
                    'page' => [
                        'id' => $techplane->task->page->id,
                        'title' => $techplane->task->page->title,
                    ],
                ],
                'creator' => [
                    'id' => $techplane->creator->id,
                    'name' => $techplane->creator->name,
                    'email' => $techplane->creator->email,
                ],
                'llm_chat' => $techplane->llmChat ? [
                    'id' => $techplane->llmChat->id,
                    'messages' => $techplane->llmChat->messages,
                ] : null,
            ]
        ]);
    }
}
