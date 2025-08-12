<?php

namespace App\Providers;

use App\Interfaces\DiffGeneratorInterface;
use App\Interfaces\LLMGenerator;
use App\Interfaces\TaskDescriptionGeneratorInterface;
use App\Interfaces\TaskTrackerInterface;
use App\Services\DiffGenerator\DiffGeneratorService;
use App\Services\LLMGenerator\LMStudioGenerator;
use App\Services\LLMGenerator\ToolsFactory;
use App\Services\TaskDescriptionGenerator\LLMDescriptionGenerator;
use App\Services\TaskDescriptionGenerator\StubDescriptionGenerator;
use App\Services\TaskManagementService;
use App\Services\TaskTracker\Integration\FakeIntegration;
use App\Services\TaskTracker\TaskTrackerService;
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

        // Регистрация сервисов генерации описания задач
        // Используем OpenAI генератор, если настроен API ключ, иначе заглушку
        $this->app->bind(TaskDescriptionGeneratorInterface::class, function ($app) {
            $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');

            if ($apiKey) {
                $llmGenerator = new LMStudioGenerator($app->get(ToolsFactory::class));
                return new LLMDescriptionGenerator($llmGenerator);
            }

            return new StubDescriptionGenerator();
        });

        // Регистрация сервиса генерации diff
        $this->app->bind(DiffGeneratorInterface::class, DiffGeneratorService::class);

        // Регистрация ToolsFactory
        $this->app->singleton(ToolsFactory::class);

        // Регистрация LLM генератора
        $this->app->bind(LLMGenerator::class, LMStudioGenerator::class);

        // Регистрация сервиса управления задачами
        $this->app->singleton(TaskManagementService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
