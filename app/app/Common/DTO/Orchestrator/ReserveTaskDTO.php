<?php

namespace App\Common\DTO\Orchestrator;

readonly class ReserveTaskDTO
{
    public function __construct(
        public int $reserve_seconds,
        public string $agent_uuid,
    ) {}
    
    /**
     * Создать DTO из массива данных
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['reserve_seconds'])) {
            throw new \InvalidArgumentException('Поле reserve_seconds обязательно');
        }
        
        if (!isset($data['agent_uuid'])) {
            throw new \InvalidArgumentException('Поле agent_uuid обязательно');
        }
        
        return new self(
            reserve_seconds: (int) $data['reserve_seconds'],
            agent_uuid: (string) $data['agent_uuid'],
        );
    }
}

