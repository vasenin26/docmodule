<?php

namespace App\Common\DTO\Orchestrator;

readonly class UpdatePublicKeyDTO
{
    public function __construct(
        public string $public_key,
    ) {}
    
    /**
     * Создать DTO из массива данных
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['public_key'])) {
            throw new \InvalidArgumentException('Поле public_key обязательно');
        }
        
        return new self(
            public_key: (string) $data['public_key'],
        );
    }
}

