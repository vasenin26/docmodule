<?php

namespace App\Models;

use App\Common\Enums\GenerationStatus;
use App\Interfaces\DisplayableResource;
use App\Models\AgentTask;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Implementation extends Model implements DisplayableResource
{
    use HasFactory;

    protected $fillable = [
        'content',
        'techplane_id',
        'chat_id',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => GenerationStatus::class,
        ];
    }

    // Связи
    public function techplane(): BelongsTo
    {
        return $this->belongsTo(Techplane::class);
    }

    public function llmChat(): BelongsTo
    {
        return $this->belongsTo(LLMChat::class, 'chat_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Методы проверки статуса (используются в контроллере)
    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    /**
     * Получить актуальный статус с учетом активных задач агента
     */
    public function actualStatus(): GenerationStatus
    {
        $activeAgentTask = AgentTask::where('chat_id', $this->chat_id)
            ->whereIn('status', [AgentTask::STATUS_WAIT, AgentTask::STATUS_PROCESSING])
            ->first();

        if ($activeAgentTask) {
            // Маппинг статусов агентских задач на статусы генерации
            return match($activeAgentTask->status) {
                AgentTask::STATUS_WAIT => GenerationStatus::PENDING,
                AgentTask::STATUS_PROCESSING => GenerationStatus::PROCESSING,
                AgentTask::STATUS_SUCCESS => GenerationStatus::COMPLETED,
                AgentTask::STATUS_FAILED => GenerationStatus::FAILED,
            };
        }

        return $this->status;
    }

    /**
     * Возвращает маршрут для отображения ресурса
     */
    public function viewPage(): string
    {
        return route('implementations.show', $this->id);
    }
}
