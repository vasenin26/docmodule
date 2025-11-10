<?php

use App\Http\Controllers\ActualizationController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImplementationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PatchController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectPagesController;
use App\Http\Controllers\ProjectPromptController;
use App\Http\Controllers\ProjectGenerationModelController;
use App\Http\Controllers\RepositoryController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\AgentTaskController;
use App\Http\Controllers\PageSearchController;
use App\Http\Controllers\TechplaneController;
use App\Http\Controllers\ExpenseSummaryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// API маршрут для получения статистики токенов
Route::get('api/dashboard/token-statistics', [DashboardController::class, 'apiTokenStatistics'])
    ->middleware(['auth', 'verified'])
    ->name('api.dashboard.token-statistics');

// Маршруты для проектов и страниц документации
Route::middleware(['auth', 'verified'])->group(function () {
    // Поиск страниц (должен быть до resource('pages'))
    Route::get('pages/search', [PageSearchController::class, 'index'])->name('pages.search');

    // Маршруты проектов
    Route::resource('projects', ProjectController::class);

    // Маршруты репозиториев проектов
    Route::post('projects/{project}/repositories', [RepositoryController::class, 'store'])
        ->name('projects.repositories.store');
    Route::delete('projects/{project}/repositories/{repository}', [RepositoryController::class, 'destroy'])
        ->name('projects.repositories.destroy');

    // Маршруты страниц в контексте проекта
    Route::get('projects/{project}/pages', [PageController::class, 'index'])->name('projects.pages.index');
    Route::get('projects/{project}/pages/create', [PageController::class, 'create'])->name('projects.pages.create');
    Route::get('projects/{project}/pages/{page}/create', [PageController::class, 'create'])->name('projects.pages.create-children');
    Route::post('projects/{project}/pages', [PageController::class, 'store'])->name('projects.pages.store');

    // Общие маршруты для страниц
    // Страница списка/поиска страниц (поддерживает query params: search, id, project_id, per_page)


    Route::resource('pages', PageController::class);
    Route::get('pages/{page}/versions', [PageController::class, 'versions'])->name('pages.versions');
    Route::post('pages/{page}/restore/{version}', [PageController::class, 'restore'])->name('pages.restore');

    // Новый маршрут для создания черновика
    Route::post('pages/{page}/create-draft', [PageController::class, 'createDraft'])->name('pages.create-draft');

    // Маршруты для работы с версиями страниц
    Route::get('pages/{page}/versions/{version}', [PageController::class, 'showVersion'])->name('pages.versions.show');
    Route::get('pages/{page}/versions/{version}/edit', [PageController::class, 'editVersion'])->name('pages.versions.edit');
    Route::put('pages/{page}/versions/{version}', [PageController::class, 'updateVersion'])->name('pages.versions.update');

    // Новые маршруты для черновиков
    Route::post('drafts/{pageVersion}/approve', [PageController::class, 'approveDraft'])->name('drafts.approve');
    Route::get('pages/{page}/draft', [PageController::class, 'getDraft'])->name('pages.draft.get');
    Route::delete('pages/{page}/draft', [PageController::class, 'deleteDraft'])->name('pages.draft.delete');

    // Маршрут для создания задачи
    Route::post('pages/{page}/create-task', [PageController::class, 'createTask'])->name('pages.create-task');

    // Маршруты актуализации
    Route::get('actualization/{actualization}/status', [ActualizationController::class, 'status'])
        ->name('actualization.status');
    Route::get('pages/{page}/actualizations', [ActualizationController::class, 'index'])
        ->name('pages.actualizations.index');
    Route::get('actualizations/{actualization}', [ActualizationController::class, 'show'])
        ->name('actualizations.show');
    Route::post('actualizations/{actualization}/restart', [ActualizationController::class, 'restart'])
        ->name('actualizations.restart');

    // Новый маршрут для актуализации конкретного черновика
    Route::post('/page/version/{version}/actualize', [ActualizationController::class, 'start'])->name('pages.versions.actualise');


    // Статус актуализации с чатом и отправка сообщений
    Route::get('actualizations/{actualization}/status-with-chat', [ActualizationController::class, 'getStatusWithChat'])
        ->name('actualizations.status-with-chat');

    // Маршруты для задач
    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::get('tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::get('tasks/{task}/status', [TaskController::class, 'checkGenerationStatus'])->name('tasks.status');
    Route::post('tasks/{task}/restart-generation', [TaskController::class, 'restartGeneration'])->name('tasks.restart-generation');
    Route::post('tasks/{task}/create-techplane', [TaskController::class, 'createTechplane'])->name('tasks.create-techplane');
    Route::post('tasks/{task}/send-message', [TaskController::class, 'sendMessage'])->name('tasks.send-message');
    Route::put('tasks/{task}/stop-generating', [TaskController::class, 'stopGenerating'])->name('tasks.stop-generating');

    // Привязки страниц к задаче
    Route::post('tasks/{task}/attachments', [TaskAttachmentController::class, 'store'])->name('tasks.attachments.store');
    Route::delete('tasks/{task}/attachments/{pageVersion}', [TaskAttachmentController::class, 'destroy'])->name('tasks.attachments.destroy');


    // Маршруты для техпланов
    Route::get('techplanes/{techplane}', [TechplaneController::class, 'show'])->name('techplanes.show');
    Route::post('techplanes/{techplane}/restart-generation', [TechplaneController::class, 'restartGeneration'])
        ->name('techplanes.restart-generation');
    Route::get('techplanes/{techplane}/check-generation-status', [TechplaneController::class, 'checkGenerationStatus'])
        ->name('techplanes.check-generation-status');
    Route::post('techplanes/{techplane}/send-message', [TechplaneController::class, 'sendMessage'])
        ->name('techplanes.send-message');
    Route::post('techplanes/{techplane}/execute', [TechplaneController::class, 'execute'])
        ->name('techplanes.execute');
    Route::put('techplanes/{techplane}/stop-generating', [TechplaneController::class, 'stopGenerating'])
        ->name('techplanes.stop-generating');

    // Скачать техплан в формате Markdown
    Route::get('techplanes/{techplane}/download-markdown', [TechplaneController::class, 'downloadMarkdown'])
        ->name('techplanes.download-markdown');

    // Маршруты для реализаций
    Route::get('implementations/{implementation}', [ImplementationController::class, 'show'])
        ->name('implementations.show');
    Route::get('implementations/{implementation}/check-status', [ImplementationController::class, 'checkStatus'])
        ->name('implementations.check-status');
    Route::post('implementations/{implementation}/send-message', [ImplementationController::class, 'sendMessage'])
        ->name('implementations.send-message');
    Route::put('implementations/{implementation}/stop-generating', [ImplementationController::class, 'stopGenerating'])
        ->name('implementations.stop-generating');

    // НОВЫЕ маршруты в рамках проекта
    Route::prefix('projects/{project}')->group(function () {
        Route::get('/pages', [ProjectPagesController::class, 'index'])->name('projects.pages.index');
        Route::get('/tasks', [TaskController::class, 'index'])->name('projects.tasks.index');
        // Новый маршрут формы создания задачи в проекте
        Route::get('/tasks/create', [TaskController::class, 'create'])->name('projects.tasks.create');
        Route::post('/tasks', [TaskController::class, 'store'])->name('projects.tasks.store');
        Route::delete('/tasks/{projectTask}', [TaskController::class, 'destroy'])->name('projects.tasks.destroy');

        // Agent tasks (project scoped)
        Route::get('/agent-tasks', [AgentTaskController::class, 'index'])
            ->name('projects.agent-tasks.index');
    });

    // Agent tasks (global)
    Route::get('agent-tasks/{id}/chat-content', [AgentTaskController::class, 'getChatContent'])
        ->name('agent-tasks.chat-content');
    Route::post('agent-tasks/check', [AgentTaskController::class, 'check'])
        ->name('agent-tasks.check');

    Route::get('agent-tasks/{id}/target-resource', [AgentTaskController::class, 'getTargetResource'])
        ->name('agent-tasks.target-resource');

    // Маршруты для промптов проекта
    Route::get('projects/{project}/prompts', [ProjectPromptController::class, 'index'])
        ->name('projects.prompts.index');
    Route::get('projects/{project}/prompts/{type}', [ProjectPromptController::class, 'show'])
        ->name('projects.prompts.show');
    Route::post('projects/{project}/prompts', [ProjectPromptController::class, 'store'])
        ->name('projects.prompts.store');
    Route::delete('projects/{project}/prompts/{type}', [ProjectPromptController::class, 'destroy'])
        ->name('projects.prompts.destroy');
    Route::post('projects/{project}/prompts/preview', [ProjectPromptController::class, 'preview'])
        ->name('projects.prompts.preview');

    Route::get('projects/{project}/generation-models', [ProjectGenerationModelController::class, 'index'])
        ->name('projects.generation-models.index');
    Route::get('projects/{project}/generation-models/{type}', [ProjectGenerationModelController::class, 'show'])
        ->name('projects.generation-models.show');
    Route::post('projects/{project}/generation-models', [ProjectGenerationModelController::class, 'store'])
        ->name('projects.generation-models.store');
    Route::delete('projects/{project}/generation-models/{type}', [ProjectGenerationModelController::class, 'destroy'])
        ->name('projects.generation-models.destroy');

    // API маршруты для проектов
    Route::get('/api/projects', [ProjectController::class, 'apiIndex'])->name('api.projects.index');
    // API маршрут для получения проекта
    Route::get('/api/projects/{project}', [ProjectController::class, 'apiShow'])->name('api.projects.show');

    // API маршрут для получения плоских страниц проекта
    Route::get('/api/projects/{projectId}/flat-pages', [\App\Http\Controllers\Api\PageController::class, 'getFlatPages'])
        ->name('api.projects.flat-pages');

    // Маршруты для агентов проекта
    Route::get('projects/{project}/agents', [AgentController::class, 'index'])
        ->name('projects.agents.index');
    Route::get('projects/{project}/agents/create', [AgentController::class, 'create'])
        ->name('projects.agents.create');
    Route::post('projects/{project}/agents', [AgentController::class, 'store'])
        ->name('projects.agents.store');
    Route::get('projects/{project}/agents/{agent}/edit', [AgentController::class, 'edit'])
        ->name('projects.agents.edit');
    Route::put('projects/{project}/agents/{agent}', [AgentController::class, 'update'])
        ->name('projects.agents.update');
    Route::delete('projects/{project}/agents/{agent}', [AgentController::class, 'destroy'])
        ->name('projects.agents.destroy');
    Route::post('projects/{project}/agents/{agent}/regenerate-token', [AgentController::class, 'regenerateToken'])
        ->name('projects.agents.regenerate-token');
    Route::post('projects/{project}/agents/{agent}/start', [AgentController::class, 'startAgent'])
        ->name('projects.agents.start');

    // Сводка расходов
    Route::get('/expense-summary', [ExpenseSummaryController::class, 'index'])
        ->name('expense-summary');
    Route::get('/expense-summary/data', [ExpenseSummaryController::class, 'getData'])
        ->name('expense-summary.data');

    PatchController::route();

    // Чат ЛЛМ
    Route::post('/chat/{chat}', [ChatController::class, 'state'])->name('chat.state');
    Route::delete('/chat/{chat}', [ChatController::class, 'stop'])->name('chat.stop');
    Route::post('/chat/{chat}/message', [ChatController::class, 'sendMessage'])->name('chat.message.send');
});



require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
