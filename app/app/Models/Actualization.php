<?php

namespace App\Models;

use App\Interfaces\DisplayableResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property LLMChat $llmChat
 */
class Actualization extends Model implements DisplayableResource
{
    use HasFactory;

    /**
     * Статусы актуализации
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_RESTARTING = 'restarting';
    const STATUS_INIT = 'init';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'page_id',           // ID страницы (для удобства запросов)
        'page_version_id',   // ID версии страницы (черновика) - ОСНОВНАЯ СВЯЗЬ
        'status',
        'llm_chat_id',
        'created_by',
    ];

    /**
     * Черновик (версия страницы), для которого выполняется актуализация
     */
    public function pageVersion(): BelongsTo
    {
        return $this->belongsTo(PageVersion::class, 'page_version_id');
    }

    /**
     * Страница, для которой выполняется актуализация (через черновик)
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    /**
     * Чат LLM, связанный с актуализацией
     */
    public function llmChat(): BelongsTo
    {
        return $this->belongsTo(LLMChat::class, 'llm_chat_id');
    }

    /**
     * Пользователь, который запустил актуализацию
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Проверить, что актуализация привязана к черновику
     */
    public function validateDraftBinding(): bool
    {
        if (!$this->page_version_id) {
            return false;
        }

        $pageVersion = $this->pageVersion;
        return $pageVersion && $pageVersion->is_draft;
    }

    /**
     * Boot метод для автоматической валидации
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($actualization) {
            if ($actualization->page_version_id) {
                $pageVersion = PageVersion::find($actualization->page_version_id);
                if (!$pageVersion || !$pageVersion->is_draft) {
                    throw new \InvalidArgumentException('Актуализация может быть привязана только к черновику');
                }
                // Автоматически устанавливаем page_id из черновика
                $actualization->page_id = $pageVersion->page_id;
            }
        });
    }

    /**
     * Получить актуальный статус с учетом активных задач агента для чата
     */
    public function generationStatus(): string
    {
        if ($this->llm_chat_id) {
            $activeAgentTask = AgentTask::where('chat_id', $this->llm_chat_id)
                ->whereIn('status', [AgentTask::STATUS_WAIT, AgentTask::STATUS_PROCESSING])
                ->first();

            if ($activeAgentTask) {
                return $activeAgentTask->status;
            }
        }

        return AgentTask::STATUS_SUCCESS;
    }

    public function isGenerating(): bool
    {
        return in_array($this->generationStatus(), [self::STATUS_PROCESSING, self::STATUS_PENDING]);
    }

    public function viewPage(): string
    {
        return route('actualizations.show', $this->id);
    }

    public function getGenerationStatus()
    {
        if ($this->status !== self::STATUS_COMPLETED) {
            return $this->status;
        }

        $chatStatus = $this->llmChat?->getStatus();

        if ($chatStatus) {
            return $chatStatus;
        }

        return $this->status;
    }
}
