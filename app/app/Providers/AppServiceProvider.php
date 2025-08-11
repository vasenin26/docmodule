<?php

namespace App\Providers;

use App\Services\DiffGenerator\DiffGeneratorInterface;
use App\Services\DiffGenerator\DiffGeneratorService;
use App\Services\LLMGenerator\LMStudioGenerator;
use App\Services\TaskDescriptionGenerator\OpenAIDescriptionGenerator;
use App\Services\TaskDescriptionGenerator\StubDescriptionGenerator;
use App\Services\TaskDescriptionGenerator\TaskDescriptionGeneratorInterface;
use App\Services\TaskTracker\FakeIntegration;
use App\Services\TaskTracker\TaskTrackerInterface;
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
                $llmGenerator = new LMStudioGenerator();
                return new OpenAIDescriptionGenerator($llmGenerator);
            }
            
            return new StubDescriptionGenerator();
        });

        // Регистрация сервиса генерации diff
        $this->app->bind(DiffGeneratorInterface::class, DiffGeneratorService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
