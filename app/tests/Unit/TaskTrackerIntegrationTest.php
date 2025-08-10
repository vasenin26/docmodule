<?php

namespace Tests\Unit;

use App\Services\TaskTracker\FakeIntegration;
use App\Services\TaskTracker\TaskTrackerInterface;
use App\Services\TaskTracker\TaskTrackerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TaskTrackerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест создания задачи через FakeIntegration
     */
    public function test_fake_integration_creates_task(): void
    {
        // Arrange
        Log::shouldReceive('info')->once();
        $integration = new FakeIntegration();
        $title = 'Тестовая задача';
        $description = 'Описание тестовой задачи';

        // Act
        $result = $integration->createTask($title, $description);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Тест корректности вывода данных в лог
     */
    public function test_fake_integration_logs_task_data(): void
    {
        // Arrange
        Log::shouldReceive('info')
            ->once()
            ->with('FakeIntegration: Создание задачи', \Mockery::on(function ($data) {
                return $data['title'] === 'Тестовая задача' &&
                       $data['description'] === 'Описание тестовой задачи' &&
                       isset($data['timestamp']);
            }));

        $integration = new FakeIntegration();
        $title = 'Тестовая задача';
        $description = 'Описание тестовой задачи';

        // Act
        $integration->createTask($title, $description);
    }

    /**
     * Тест расширяемости системы через TaskTrackerService
     */
    public function test_task_tracker_service_uses_integration(): void
    {
        // Arrange
        $mockIntegration = \Mockery::mock(TaskTrackerInterface::class);
        $mockIntegration->shouldReceive('createTask')
            ->once()
            ->with('Тестовая задача', 'Описание тестовой задачи')
            ->andReturn(true);

        $service = new TaskTrackerService($mockIntegration);
        $title = 'Тестовая задача';
        $description = 'Описание тестовой задачи';

        // Act
        $result = $service->createTask($title, $description);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Тест получения интеграции из сервиса
     */
    public function test_task_tracker_service_returns_integration(): void
    {
        // Arrange
        $integration = new FakeIntegration();
        $service = new TaskTrackerService($integration);

        // Act
        $returnedIntegration = $service->getIntegration();

        // Assert
        $this->assertSame($integration, $returnedIntegration);
        $this->assertInstanceOf(TaskTrackerInterface::class, $returnedIntegration);
    }

    /**
     * Тест регистрации сервисов в контейнере
     */
    public function test_services_are_registered_in_container(): void
    {
        // Act & Assert
        $this->assertInstanceOf(FakeIntegration::class, app(TaskTrackerInterface::class));
        $this->assertInstanceOf(TaskTrackerService::class, app(TaskTrackerService::class));
    }
}
