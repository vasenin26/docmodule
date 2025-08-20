<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Techplane extends Model
{
    protected $fillable = [
        'task_id',
        'content', 
        'created_by',
        'chat_id',
        'generation_status',
    ];

    // Константы статусов
    public const STATUS_PENDING = 'pending';
    public const STATUS_GENERATING = 'generating'; 
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

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

    // Методы проверки статуса
    public function isCompleted(): bool
    {
        return $this->generation_status === self::STATUS_COMPLETED;
    }

    public function isGenerating(): bool
    {
        return $this->generation_status === self::STATUS_GENERATING;
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
