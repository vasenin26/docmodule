<?php

namespace App\Providers;

use App\Factory\AgentFactory;
use App\Interfaces\ContentGenerator\DiffGeneratorInterface;
use App\Interfaces\Factory\AgentFactoryInterface;
use App\Interfaces\Factory\TaskDescriptionGeneratorFactoryInterface;
use App\Interfaces\Factory\TechplaneGeneratorFactoryInterface;
use App\Interfaces\Factory\ToolServiceFactoryInterface;
use App\Interfaces\GitRepoProviderInterface;
use App\Interfaces\LLM\ContentGenerator;
use App\Interfaces\PageContextServiceFactoryInterface;
use App\Interfaces\TaskTrackerInterface;
use App\Services\DiffGenerator\DiffGeneratorService;
use App\Services\LLMGenerator\LMStudioClient;
use App\Services\PageContextServiceFactory;
use App\Services\RepositoryService\RepositoryProvider;
use App\Services\TaskDescriptionGenerator\TaskDescriptionGeneratorFactory;
use App\Services\TaskDescriptionGenerator\TechplaneGeneratorFactory;
use App\Services\TaskManagementService;
use App\Services\TaskTracker\Integration\FakeIntegration;
use App\Services\TaskTracker\TaskTrackerService;
use App\Services\ToolsService\ToolServiceFactory;
use Illuminate\Support\ServiceProvider;

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

        $this->app->bind(AgentFactoryInterface::class, AgentFactory::class);
        $this->app->bind(TaskDescriptionGeneratorFactoryInterface::class, TaskDescriptionGeneratorFactory::class);
        $this->app->bind(TechplaneGeneratorFactoryInterface::class, TechplaneGeneratorFactory::class);

        // Регистрация сервиса генерации diff
        $this->app->bind(DiffGeneratorInterface::class, DiffGeneratorService::class);

        // Регистрация LLM генератора
        $this->app->bind(ToolServiceFactoryInterface::class, ToolServiceFactory::class);
        $this->app->bind(ContentGenerator::class, LMStudioClient::class);

        // Регистрация сервиса управления задачами
        $this->app->singleton(TaskManagementService::class);

        $this->app->singleton(GitRepoProviderInterface::class, RepositoryProvider::class);
        
        // Регистрация фабрики PageContextService
        $this->app->bind(
            PageContextServiceFactoryInterface::class,
            PageContextServiceFactory::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
