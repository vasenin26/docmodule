<?php

namespace App\Services\TaskTracker\Integration\YouGile;

use App\Common\RestApiClient\RestApiClient;
use App\Interfaces\KeyProviderInterface;
use App\Interfaces\TaskTrackerInterface;
use App\Services\TaskTracker\Integration\YouGile\Command\AuthCommand;
use App\Services\TaskTracker\Integration\YouGile\Command\CreateTaskCommand;

class YouGile implements TaskTrackerInterface
{
    public function __construct(
        private KeyProviderInterface $keyProvider,
        private RestApiClient $restApiClient,
        private string $login,
        private string $password,
        private string $companyId
    )
    {
    }

    public function factory(KeyProviderInterface $keyProvider): TaskTrackerInterface
    {
        return new self(
            $keyProvider,
            $this->restApiClient,
            $this->login,
            $this->password,
            $this->companyId
        );
    }

    public function createTask(string $title, string $description): bool
    {
        $key = $this->keyProvider->getKey();

        throw_if(is_null($key));

        $token = (new AuthCommand($this->login, $this->password, $this->companyId))->execute($this->restApiClient);
        $task = (new CreateTaskCommand($token, $title, $description))->execute($this->restApiClient);

        return isset($task['id']);
    }

    public function createTaskWithDetails(
        string $title,
        string $description,
        string $projectId,
        string $columnId,
        array $assignees = [],
        string $dueDate = null,
        int $priority = null
    ): array {
        $token = $this->authenticate();
        
        $command = CreateTaskCommand::createFull(
            $token,
            $title,
            $description,
            $projectId,
            $columnId,
            $assignees,
            $dueDate,
            $priority
        );
        
        return $command->execute($this->restApiClient);
    }

    public function authenticate(): string
    {
        return (new AuthCommand($this->login, $this->password, $this->companyId))->execute($this->restApiClient);
    }
}
