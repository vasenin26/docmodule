<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Terminal extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'chat_id',
        'created_by',
    ];

    /**
     * Связь с проектом
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Связь с чатом
     */
    public function llmChat(): BelongsTo
    {
        return $this->belongsTo(LLMChat::class, 'chat_id');
    }

    /**
     * Связь с создателем
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope для фильтрации терминалов по создателю
     * Пользователю доступны только те терминалы, которые он создал
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', $userId);
    }
}

