<?php

namespace App\Http\Controllers;

use App\Http\Resources\AgentTaskResource;
use App\Models\AgentTask;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AgentTaskController extends Controller
{
    public function index(Request $request, ?Project $project = null)
    {
        $query = AgentTask::with(['project', 'creator', 'llmChat', 'agent'])
            ->orderBy('created_at', 'desc');

        if ($project) {
            $query->where('project_id', $project->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('handler', 'like', "%{$search}%")
                  ->orWhere('agent_uuid', 'like', "%{$search}%")
                  ->orWhere('agent_model', 'like', "%{$search}%")
                  ->orWhere('chat_id', 'like', "%{$search}%")
                  ->orWhere('context_id', 'like', "%{$search}%");

                // точное совпадение по id, если число
                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }

                $q->orWhereHas('project', function ($q2) use ($search) {
                    $q2->where('title', 'like', "%{$search}%");
                });

                $q->orWhereHas('creator', function ($q3) use ($search) {
                    $q3->where('name', 'like', "%{$search}%");
                });
            });
        }

        $paginated = $query->paginate(15)->withQueryString();

        $items = AgentTaskResource::collection($paginated->items())->resolve();

        $links = collect($paginated->linkCollection()->toArray())->map(function ($l) {
            return [
                'url' => $l['url'] ?? null,
                'label' => $l['label'] ?? null,
                'active' => $l['active'] ?? false,
            ];
        })->toArray();

        $tasks = [
            'data' => $items,
            'links' => $links,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ];

        return Inertia::render('agent-tasks/Index', [
            'tasks' => $tasks,
            'project' => $project ? ['id' => $project->id, 'title' => $project->title] : null,
            'filters' => [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
            ],
        ]);
    }
}


