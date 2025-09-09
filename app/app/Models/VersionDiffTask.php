<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VersionDiffTask extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\VersionDiffTaskFactory::new();
    }

    protected $fillable = [
        'page_id',
        'page_version_id',
        'content',
        'created_by',
        'generation_status',
        'llm_chat_id',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
        ];
    }

    // Константы для статусов
    public const STATUS_PENDING = 'pending';
    public const STATUS_GENERATING = 'generating';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Связь с версией страницы
     */
    public function pageVersion(): BelongsTo
    {
        return $this->belongsTo(PageVersion::class, 'page_version_id');
    }

    /**
     * Связь с страницей через версию
     */
    public function getPageAttribute()
    {
        return $this->pageVersion->page;
    }

    /**
     * Связь с моделью User (создатель)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Связь с моделью LLMChat
     */
    public function llmChat(): BelongsTo
    {
        return $this->belongsTo(LLMChat::class, 'llm_chat_id');
    }

    /**
     * Проверить, находится ли описание в процессе генерации
     */
    public function isGenerating(): bool
    {
        return $this->generation_status === self::STATUS_GENERATING;
    }

    /**
     * Проверить, завершена ли генерация описания
     */
    public function isCompleted(): bool
    {
        return $this->generation_status === self::STATUS_COMPLETED;
    }

    /**
     * Проверить, произошла ли ошибка при генерации
     */
    public function hasFailed(): bool
    {
        return $this->generation_status === self::STATUS_FAILED;
    }

    /**
     * Связь с техпланом
     */
    public function techplane(): HasOne
    {
        return $this->hasOne(Techplane::class, 'task_id');
    }

    /**
     * Отметить задачу как отредактированную
     */
    public function markAsEdited(): void
    {
        $this->update(['edited_at' => now()]);
    }

    /**
     * Очистить связанный техплан
     */
    public function clearTechplane(): void
    {
        if ($this->techplane) {
            $this->techplane->clear();
        }
    }
}
