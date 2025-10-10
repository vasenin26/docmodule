<?php

namespace App\Models;

use App\Common\Enums\GenerationStatus;
use App\Models\Implementation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Techplane extends Model
{
    protected $fillable = [
        'task_id',
        'content',
        'created_by',
        'chat_id',
        'generation_status',
        'status',
    ];



    // Константы статусов генерации
    public const STATUS_PENDING = 'pending';
    public const STATUS_GENERATING = 'generating';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    // Константы статусов исполнения
    public const EXECUTION_PLANNED = 'planned';
    public const EXECUTION_EXECUTED = 'executed';

    // Связи
    public function task(): BelongsTo
    {
        return $this->belongsTo(VersionDiffTask::class, 'task_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function llmChat(): BelongsTo
    {
        return $this->belongsTo(LLMChat::class, 'chat_id');
    }

    // Связь с реализациями
    public function implementations(): HasMany
    {
        return $this->hasMany(Implementation::class);
    }

    // Связь с решениями (solutions) через pivot techplane_solution
    public function solutions(): BelongsToMany
    {
        return $this->belongsToMany(Solution::class, 'techplane_solution', 'techplane_id', 'solution_id');
    }

    // Метод для создания реализации
    public function createImplementation(int $userId): Implementation
    {
        return $this->implementations()->create([
            'created_by' => $userId,
            'status' => GenerationStatus::PENDING,
        ]);
    }

    // Методы проверки статуса
    public function isCompleted(): bool
    {
        return $this->generation_status === self::STATUS_COMPLETED;
    }

    public function isGenerating(): bool
    {
        return $this->generation_status === self::STATUS_GENERATING;
    }

    // Статус исполнения
    public function isExecuted(): bool
    {
        return $this->status === self::EXECUTION_EXECUTED;
    }

    public function markExecuted(): void
    {
        if ($this->status !== self::EXECUTION_EXECUTED) {
            $this->update(['status' => self::EXECUTION_EXECUTED]);
        }
    }

    /**
     * Получить актуальный статус генерации с учетом активных задач агента
     */
    public function generationStatus(): string
    {
        $activeAgentTask = AgentTask::where('chat_id', $this->chat_id)
            ->whereIn('status', [AgentTask::STATUS_WAIT, AgentTask::STATUS_PROCESSING])
            ->first();

        if ($activeAgentTask) {
            return $activeAgentTask->status;
        }

        return $this->generation_status;
    }

    /**
     * Очистить содержимое техплана
     */
    public function clear(): void
    {
        $this->update([
            'content' => null,
            'generation_status' => self::STATUS_PENDING
        ]);
    }

    /**
     * Отметить техплан как очищенный
     */
    public function markAsCleared(): void
    {
        $this->update([
            'generation_status' => self::STATUS_PENDING
        ]);
    }
}
