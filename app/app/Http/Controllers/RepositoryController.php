<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Repository;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RepositoryController extends Controller
{
    /**
     * Store a newly created repository in storage and attach to project.
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        // Проверяем доступ к проекту
        if ($project->owner_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'url' => [
                'required',
                'url',
                'max:255',
                function ($attribute, $value, $fail) use ($project) {
                    // Проверяем, существует ли уже такой репозиторий в этом проекте
                    $existingRepository = Repository::where('url', $value)->first();
                    if ($existingRepository && $project->repositories()->where('repository_id', $existingRepository->id)->exists()) {
                        $fail('Этот репозиторий уже добавлен к проекту');
                    }
                },
            ],
        ], [
            'url.required' => 'URL репозитория обязателен для заполнения',
            'url.url' => 'Введите корректный URL',
        ]);

        // Используем паттерн "найти или создать" для Repository модели
        $repository = Repository::firstOrCreate(
            ['url' => $validated['url']],
            ['url' => $validated['url']]
        );

        // Проверяем, не добавлен ли уже этот репозиторий к проекту
        if (!$project->repositories()->where('repository_id', $repository->id)->exists()) {
            $project->repositories()->attach($repository->id);
        }

        return redirect()->route('projects.edit', $project)
            ->with('success', 'Репозиторий успешно добавлен к проекту');
    }

    /**
     * Remove the repository from the project.
     */
    public function destroy(Project $project, Repository $repository): RedirectResponse
    {
        // Проверяем доступ к проекту
        if ($project->owner_id !== Auth::id()) {
            abort(403);
        }

        // Отсоединяем репозиторий от проекта
        $project->repositories()->detach($repository->id);

        // Обновляем модель репозитория, чтобы корректно получить количество связей
        $repository->refresh();
        
        // Если репозиторий больше не привязан ни к одному проекту, удаляем его
        if ($repository->projects()->count() === 0) {
            $repository->delete();
        }

        return redirect()->route('projects.edit', $project)
            ->with('success', 'Репозиторий успешно удален из проекта');
    }
}
