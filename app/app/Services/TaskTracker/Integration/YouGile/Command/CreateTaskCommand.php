<?php

namespace App\Services\TaskTracker\Integration\YouGile\Command;

use App\Common\RestApiClient\Command;
use App\Common\RestApiClient\RestApiClient;
use GuzzleHttp\Psr7\Request;

class CreateTaskCommand extends Request implements Command
{
    public function __construct(
        private string $authToken,
        private string $title,
        private string $description,
        private ?string $projectId = null,
        private ?string $columnId = null,
        private ?array $assignees = null,
        private ?string $dueDate = null,
        private ?int $priority = null
    ) {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->authToken
        ];

        $taskData = [
            'title' => $this->title,
            'description' => $this->description
        ];

        // Добавляем опциональные поля, если они предоставлены
        if ($this->projectId) {
            $taskData['projectId'] = $this->projectId;
        }
        
        if ($this->columnId) {
            $taskData['columnId'] = $this->columnId;
        }
        
        if ($this->assignees) {
            $taskData['assignees'] = $this->assignees;
        }
        
        if ($this->dueDate) {
            $taskData['dueDate'] = $this->dueDate;
        }
        
        if ($this->priority !== null) {
            $taskData['priority'] = $this->priority;
        }

        parent::__construct(
            'POST',
            'https://ru.yougile.com/api-v2/tasks',
            $headers,
            json_encode($taskData)
        );
    }

    public function execute(RestApiClient $client): array
    {
        $response = $client->execute($this);
        
        if ($response->getStatusCode() !== 201 && $response->getStatusCode() !== 200) {
            throw new \RuntimeException(
                'Failed to create task in YouGile. Status: ' . $response->getStatusCode()
            );
        }

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        if (!$data) {
            throw new \RuntimeException('Invalid JSON response from YouGile');
        }

        return $data;
    }

    /**
     * Создает команду с минимальными обязательными параметрами
     */
    public static function createSimple(
        string $authToken,
        string $title,
        string $description
    ): self {
        return new self($authToken, $title, $description);
    }

    /**
     * Создает команду с полным набором параметров
     */
    public static function createFull(
        string $authToken,
        string $title,
        string $description,
        string $projectId,
        string $columnId,
        array $assignees = [],
        string $dueDate = null,
        int $priority = null
    ): self {
        return new self(
            $authToken,
            $title,
            $description,
            $projectId,
            $columnId,
            $assignees,
            $dueDate,
            $priority
        );
    }
}
