<?php

namespace App\Http\Controllers;

use App\Common\DTO\PromptDTO;
use App\Common\Enums\PromptType;
use App\Factory\PromptProviderFactory;
use App\Http\Requests\Prompt\StorePromptRequest;
use App\Models\Project;
use App\Models\Prompt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProjectPromptController extends Controller
{
    public function __construct(
        private readonly PromptProviderFactory $promptServiceFactory,
    ) {}

    public function index(Project $project): Response
    {
        if ($project->owner_id !== Auth::id()) {
            abort(403);
        }

        // Получаем все типы промптов с их содержимым
        $promptService = $this->promptServiceFactory->createProjectPromptService($project->id);
        $defaultPromptService = $this->promptServiceFactory->createDefaultPromptService();

        $prompts = [];
        foreach (PromptType::cases() as $type) {
            $projectPrompt = $project->prompts()->where('type', $type->value)->first();
            $defaultPrompt = $defaultPromptService->getPrompt($type);

            $prompts[] = [
                'type' => $type->value,
                'label' => $type->getLabel(),
                'content' => $projectPrompt?->content ?? $defaultPrompt ?? '',
                'is_default' => !$projectPrompt,
                'can_reset' => (bool) $projectPrompt,
            ];
        }

        return Inertia::render('projects/Prompts', [
            'project' => $project,
            'prompts' => $prompts,
            'prompt_types' => collect(PromptType::cases())->map(fn($type) => [
                'value' => $type->value,
                'label' => $type->getLabel(),
            ]),
        ]);
    }

    public function show(Project $project, string $type): array
    {
        if ($project->owner_id !== Auth::id()) {
            abort(403);
        }

        $promptType = PromptType::from($type);
        $promptService = $this->promptServiceFactory->createProjectPromptService($project->id);
        $defaultPromptService = $this->promptServiceFactory->createDefaultPromptService();

        $projectPrompt = $project->prompts()->where('type', $type)->first();
        $defaultPrompt = $defaultPromptService->getPrompt($promptType);

        return [
            'type' => $type,
            'label' => $promptType->getLabel(),
            'content' => $projectPrompt?->content ?? $defaultPrompt ?? '',
            'is_default' => !$projectPrompt,
            'can_reset' => (bool) $projectPrompt,
        ];
    }

    public function store(StorePromptRequest $request, Project $project): array
    {
        $promptType = PromptType::from($request->input('type'));

        $prompt = Prompt::updateOrCreate(
            [
                'type' => $promptType->value,
                'project_id' => $project->id
            ],
            [
                'content' => $request->input('content'),
                'created_by' => $request->user()->id,
            ]
        );

        return [
            'success' => true,
            'message' => 'Промпт успешно сохранён',
            'prompt' => [
                'type' => $prompt->type,
                'content' => $prompt->content,
                'is_default' => false,
                'can_reset' => true,
            ],
        ];
    }

    public function destroy(Project $project, string $type): array
    {
        if ($project->owner_id !== Auth::id()) {
            abort(403);
        }

        $project->prompts()->where('type', $type)->delete();

        return [
            'success' => true,
            'message' => 'Промпт сброшен к значению по умолчанию',
        ];
    }

    public function preview(Request $request, Project $project): array
    {
        if ($project->owner_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'type' => ['required', Rule::enum(PromptType::class)],
            'content' => ['required', 'string'],
        ]);

        $promptType = PromptType::from($request->input('type'));
        $content = $request->input('content');

        // Создаем временный сервис с mock данными
        $templateRenderer = app(\App\Services\PromptProvider\Interface\PromptTemplateRendererInterface::class);

        // Фиктивные данные для предварительного просмотра
        $mockData = [
            'diff' => "Пример изменений в коде\n+ добавленная строка\n- удаленная строка",
            'files_changed' => ['src/example.php', 'tests/ExampleTest.php'],
            'repositories' => [
                ['name' => 'main-repo', 'url' => 'https://github.com/example/repo'],
            ],
            'attached_files' => [
                ['name' => 'README.md', 'content' => 'Пример содержимого файла'],
            ],
        ];

        try {
            $rendered = $templateRenderer->render($content, $mockData);

            return [
                'success' => true,
                'rendered_content' => $rendered,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Ошибка рендеринга шаблона: ' . $e->getMessage(),
            ];
        }
    }
}
