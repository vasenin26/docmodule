<?php

namespace App\Models;

use App\Common\Enums\AgentTaskType;
use App\Interfaces\AgentTaskManagerInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'handler',
        'handler_options',
        'project_id',
        'created_by',
        'parent_id',
        'chat_id',
        'status',
        'agent_uuid',
        'agent_id',
        'agent_model',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'result_required',
        'context_id',
        'timeout',
        'reserved_at',
        'reserved_until',
        'reserved_seconds',
    ];

    public function getContextId(): string
    {
        return 'chat_' . $this['chat_id'];
    }

    protected function casts(): array
    {
        return [
            'handler_options' => 'array',
            'result_required' => 'boolean',
            'type' => AgentTaskType::class,
            'reserved_at' => 'datetime',
            'reserved_until' => 'datetime',
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'total_tokens' => 'integer',
        ];
    }

    public function getPromptTokensOrZero(): int
    {
        return $this->prompt_tokens ?? 0;
    }

    public function getCompletionTokensOrZero(): int
    {
        return $this->completion_tokens ?? 0;
    }

    public function getTotalTokensOrZero(): int
    {
        return $this->total_tokens ?? 0;
    }

    // Константы статусов для type safety
    public const STATUS_WAIT = 'wait';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    // Массив всех возможных статусов для валидации
    public const STATUSES = [
        self::STATUS_WAIT,
        self::STATUS_PROCESSING,
        self::STATUS_SUCCESS,
        self::STATUS_FAILED,
    ];

    // Eloquent Relations
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function llmChat(): BelongsTo
    {
        return $this->belongsTo(LLMChat::class, 'chat_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Родительская задача (если есть)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Дочерние задачи
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    // Status check methods
    public function isWaiting(): bool
    {
        return $this->status === self::STATUS_WAIT;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isFinished(): bool
    {
        return $this->isCompleted() || $this->isFailed();
    }

    // Scopes для удобных запросов
    public function scopeWaiting($query)
    {
        return $query->where('status', self::STATUS_WAIT);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeForAgent($query, string $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    // Scope для поиска по UUID агента
    public function scopeForAgentUuid($query, string $agentUuid)
    {
        return $query->where('agent_uuid', $agentUuid);
    }

    public function scopeStuck($query, int $minutesAgo = 30)
    {
        return $query->where('status', self::STATUS_PROCESSING)
                    ->where('updated_at', '<', now()->subMinutes($minutesAgo));
    }

    /**
     * Scope для выборки задач доступных для оркестратора
     * Включает свободные задачи и задачи с истекшим резервированием
     */
    public function scopeAvailableForOrchestrator($query)
    {
        return $query->where('status', self::STATUS_WAIT)
            ->where(function ($q) {
                $q->where(function ($q1) {
                    // Полностью свободные задачи
                    $q1->whereNull('agent_id')
                       ->whereNull('agent_uuid');
                })->orWhere(function ($q2) {
                    // Зарезервированные, но с истекшим резервированием
                    $q2->whereNotNull('reserved_until')
                       ->where('reserved_until', '<', now());
                });
            });
    }

    /**
     * Scope для задач с истекшим резервированием
     */
    public function scopeWithExpiredReservation($query)
    {
        return $query->whereNotNull('reserved_until')
                     ->where('reserved_until', '<', now());
    }

    /**
     * Проверка, зарезервирована ли задача
     */
    public function isReserved(): bool
    {
        return $this->reserved_until !== null && $this->reserved_until->isFuture();
    }

    /**
     * Проверка, истекло ли резервирование
     */
    public function reservationExpired(): bool
    {
        return $this->reserved_until !== null && $this->reserved_until->isPast();
    }

    /**
     * Резервирование задачи на указанное количество секунд
     */
    public function reserve(int $seconds, int $agentId, string $agentUuid): void
    {
        $this->update([
            'agent_id' => $agentId,
            'agent_uuid' => $agentUuid,
            'reserved_at' => now(),
            'reserved_until' => now()->addSeconds($seconds),
            'reserved_seconds' => $seconds,
        ]);
    }

    /**
     * Остановить все активные (processing) задачи для указанного чата и вернуть их идентификаторы
     */
    public static function stopGeneratingForChat(int $chatId, AgentTaskManagerInterface $agentTaskManager): array
    {
        $agentTasks = self::where([
            'chat_id' => $chatId,
            'status' => self::STATUS_PROCESSING,
        ])->get();

        $stoppedTaskIds = [];

        foreach ($agentTasks as $agentTask) {
            $agentTaskManager->stopTask($agentTask->id);
            $stoppedTaskIds[] = $agentTask->id;
        }

        return $stoppedTaskIds;
    }
}
