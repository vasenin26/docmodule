<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageDiffDescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'content',
        'created_by',
        'generation_status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'generation_status' => 'string',
    ];

    // Константы для статусов
    public const STATUS_PENDING = 'pending';
    public const STATUS_GENERATING = 'generating';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Связь с моделью Page
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Связь с моделью User (создатель)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
}
