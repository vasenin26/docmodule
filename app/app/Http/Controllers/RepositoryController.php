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
                'max:255',
                function ($attribute, $value, $fail) use ($project) {
                    // Проверка HTTPS формата
                    if (filter_var($value, FILTER_VALIDATE_URL) && str_starts_with($value, 'https://')) {
                        // Проверяем, что это GitHub репозиторий
                        if (!str_contains($value, 'github.com')) {
                            $fail('Поддерживаются только GitHub репозитории');
                            return;
                        }
                    }
                    // Проверка SSH формата
                    elseif (str_starts_with($value, 'git@')) {
                        $parts = explode(':', $value);
                        if (count($parts) !== 2 || !str_contains($parts[0], '@')) {
                            $fail('Неверный формат SSH URL');
                            return;
                        }
                        if (!str_contains($parts[0], 'github.com')) {
                            $fail('Поддерживаются только GitHub репозитории');
                            return;
                        }
                    }
                    else {
                        $fail('URL должен быть в формате HTTPS или SSH');
                        return;
                    }
                    
                    // Проверяем, существует ли уже такой репозиторий в этом проекте
                    $existingRepository = Repository::where('url', $value)->first();
                    if ($existingRepository && $project->repositories()->where('repository_id', $existingRepository->id)->exists()) {
                        $fail('Этот репозиторий уже добавлен к проекту');
                    }
                },
            ],
        ], [
            'url.required' => 'URL репозитория обязателен для заполнения',
            'url.max' => 'URL репозитория не может быть длиннее 255 символов',
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
