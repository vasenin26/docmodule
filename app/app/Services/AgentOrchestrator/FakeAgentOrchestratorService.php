<?php

namespace App\Services\AgentOrchestrator;

use App\Common\DTO\RemoteAgent\AgentMeta;
use App\Common\DTO\RemoteAgent\ConfigOptions;
use App\Interfaces\AgentOrchestratorInterface;
use Illuminate\Support\Facades\Log;

/**
 * Сервис-заглушка для имитации работы оркестратора агентов
 * 
 * Используется для разработки и тестирования. Генерирует фейковые публичные ключи
 * и логирует все операции. При внедрении реального оркестратора необходимо только
 * изменить binding в AppServiceProvider.
 */
class FakeAgentOrchestratorService implements AgentOrchestratorInterface
{
    /**
     * Регистрация агента в оркестраторе (заглушка)
     * 
     * @param ConfigOptions $configOptions Конфигурация агента (UUID, токен)
     * @return AgentMeta Метаданные зарегистрированного агента
     */
    public function startAgent(ConfigOptions $configOptions): AgentMeta
    {
        Log::info('FakeAgentOrchestrator: startAgent called', [
            'agent_id' => $configOptions->agentId->toString(),
        ]);

        // Имитируем задержку регистрации (опционально для тестирования)
        // sleep(2);

        // Генерируем фейковый публичный ключ для разработки
        $fakePublicKey = $this->generateFakePublicKey();

        Log::info('FakeAgentOrchestrator: Agent registered successfully', [
            'agent_id' => $configOptions->agentId->toString(),
            'public_key_length' => strlen($fakePublicKey),
        ]);

        return new AgentMeta(
            server: 'fake-orchestrator-server',
            agentId: $configOptions->agentId->toString(),
            publicKey: $fakePublicKey,
        );
    }

    /**
     * Остановка агента в оркестраторе (заглушка)
     * 
     * @param AgentMeta $meta Метаданные агента
     * @return AgentMeta Обновленные метаданные агента
     */
    public function stopAgent(AgentMeta $meta): AgentMeta
    {
        Log::info('FakeAgentOrchestrator: stopAgent called', [
            'agent_id' => $meta->agentId,
            'server' => $meta->server,
        ]);

        return $meta;
    }

    /**
     * Запуск процесса (заглушка)
     * 
     * @param string $taskType Тип задачи для запуска
     */
    public function startProcess(string $taskType): void
    {
        Log::info('FakeAgentOrchestrator: startProcess called', [
            'task_type' => $taskType,
        ]);
    }

    /**
     * Генерация фейкового публичного ключа в формате SSH RSA
     * 
     * Генерирует валидный по формату SSH RSA публичный ключ для имитации
     * реального ответа от оркестратора.
     * 
     * @return string Публичный ключ в формате SSH RSA
     */
    private function generateFakePublicKey(): string
    {
        // Генерируем 256 случайных байт и кодируем в base64
        // Реальные ключи RSA обычно 2048-4096 бит, мы используем 2048 бит = 256 байт
        $randomPart = base64_encode(random_bytes(256));
        
        // Формат SSH RSA: ssh-rsa [base64_encoded_key] [comment]
        return "ssh-rsa {$randomPart} fake-orchestrator-key";
    }
}
