<?php

namespace App\Services;

use App\Models\Project;

class AgentNameGenerator
{
    private array $adjectives = [
        'Smart', 'Fast', 'Reliable', 'Efficient', 'Powerful', 'Quick', 'Intelligent',
        'Advanced', 'Modern', 'Dynamic', 'Agile', 'Robust', 'Secure', 'Stable',
        'Flexible', 'Adaptive', 'Innovative', 'Creative', 'Precise', 'Accurate'
    ];
    
    private array $nouns = [
        'Agent', 'Bot', 'Assistant', 'Helper', 'Worker', 'Processor', 'Engine',
        'System', 'Service', 'Manager', 'Controller', 'Handler', 'Executor',
        'Analyzer', 'Generator', 'Optimizer', 'Solver', 'Calculator', 'Monitor'
    ];
    
    /**
     * Генерирует уникальное имя агента для проекта
     */
    public function generate(Project $project): string
    {
        $maxAttempts = 50;
        $attempt = 0;
        
        do {
            $name = $this->generateRandomName();
            $attempt++;
        } while ($this->nameExists($project, $name) && $attempt < $maxAttempts);
        
        if ($attempt >= $maxAttempts) {
            // Если не удалось сгенерировать уникальное имя, добавляем timestamp
            $name = $this->generateRandomName() . '_' . time();
        }
        
        return $name;
    }
    
    /**
     * Генерирует случайное имя из прилагательного + существительного
     */
    private function generateRandomName(): string
    {
        $adjective = $this->adjectives[array_rand($this->adjectives)];
        $noun = $this->nouns[array_rand($this->nouns)];
        
        return $adjective . ' ' . $noun;
    }
    
    /**
     * Проверяет, существует ли имя агента в проекте
     */
    private function nameExists(Project $project, string $name): bool
    {
        return $project->agents()->where('name', $name)->exists();
    }
}
