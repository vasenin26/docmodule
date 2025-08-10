<?php

namespace App\Providers;

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
                return new OpenAIDescriptionGenerator();
            }
            
            return new StubDescriptionGenerator();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
