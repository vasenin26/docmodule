<?php

namespace App\Providers;

use App\Factory\AgentOrchestratorFactory;
use App\Factory\AgentResultHandlerFactory;
use App\Factory\ChatFactory;
use App\Factory\PageContextServiceFactory;
use App\Factory\PromptProviderFactory;
use App\Interfaces\AgentOrchestratorInterface;
use App\Interfaces\AgentTaskManagerInterface;
use App\Interfaces\ContentGenerator\DiffGeneratorInterface;
use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use App\Interfaces\Factory\LLMChatFactoryInterface;
use App\Interfaces\GitRepoProviderInterface;
use App\Interfaces\PageContextServiceFactoryInterface;
use App\Interfaces\TaskTrackerInterface;
use App\Models\Project;
use App\Policies\ProjectPolicy;
use App\Services\AgentTaskManager\AgentTaskManagerService;
use App\Services\DiffGenerator\DiffGeneratorService;
use App\Services\PromptProvider\Interface\PromptSourceFactoryInterface;
use App\Services\PromptProvider\Interface\PromptTemplateRendererInterface;
use App\Services\PromptProvider\PromptSourceFactory;
use App\Services\PromptProvider\PromptTemplateRenderer;
use App\Services\RepositoryService\RepositoryProvider;
use App\Services\TaskTracker\Integration\FakeIntegration;
use App\Services\TaskTracker\TaskTrackerService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use App\Models\VersionDiffTask;
use App\Models\PageVersion;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Регистрация сервисов интеграции с таск-трекерами
        $this->app->bind(TaskTrackerInterface::class, FakeIntegration::class);
        $this->app->singleton(TaskTrackerService::class);

        // Регистрация сервиса генерации diff
        $this->app->bind(DiffGeneratorInterface::class, DiffGeneratorService::class);


        // Регистрация сервиса управления задачами агентов
        $this->app->bind(
            AgentTaskManagerInterface::class,
            AgentTaskManagerService::class
        );

        // Регистрация оркестратора агентов через фабрику
        $this->app->singleton(
            AgentOrchestratorInterface::class,
            fn() => AgentOrchestratorFactory::create()
        );

        // @deprecate проект не управляет репозиториями
        $this->app->singleton(GitRepoProviderInterface::class, RepositoryProvider::class);

        // Регистрация фабрики PageContextService
        $this->app->bind(
            PageContextServiceFactoryInterface::class,
            PageContextServiceFactory::class
        );

        // Регистрация сервисов системы промптов
        $this->app->bind(PromptTemplateRendererInterface::class, PromptTemplateRenderer::class);
        $this->app->bind(PromptSourceFactoryInterface::class, PromptSourceFactory::class);
        $this->app->bind(PromptProviderFactory::class, PromptProviderFactory::class);

        $this->app->bind(AgentResultHandlerFactoryInterface::class, AgentResultHandlerFactory::class);

        // Регистрация фабрики чатов
        $this->app->bind(LLMChatFactoryInterface::class, ChatFactory::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);
        
        // Явное связывание для параметра projectTask
        Route::model('projectTask', VersionDiffTask::class);
        
        // Кастомный биндинг для параметра page_version
        Route::bind('page_version', function ($value) {
            return PageVersion::findOrFail($value);
        });
        
        // Регистрация Observer для AgentTask
        \App\Models\AgentTask::observe(\App\Observers\AgentTaskObserver::class);
    }
}
