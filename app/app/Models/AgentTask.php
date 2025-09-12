<?php

namespace App\Models;

use App\Common\Enums\AgentTaskType;
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
        'chat_id',
        'status',
        'agent_uuid',
        'agent_id',
        'result_required',
    ];

    protected function casts(): array
    {
        return [
            'handler_options' => 'array',
            'result_required' => 'boolean',
            'type' => AgentTaskType::class,
        ];
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
}
