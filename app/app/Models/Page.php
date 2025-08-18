<?php

namespace App\Models;

use App\Common\DTO\PageVersionDTO;
use App\Observers\PageObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([PageObserver::class])]
class Page extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'version_id',
        'created_by',
        'deleted_by',
        'deleted_at',
        'project_id',
    ];

    protected $dates = [
        'deleted_at',
    ];

    /**
     * Пользователь, создавший страницу
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Пользователь, удаливший страницу
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Проект, к которому принадлежит страница
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Текущая версия страницы
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(PageVersion::class, 'version_id');
    }

    /**
     * Все версии страницы
     */
    public function versions(): HasMany
    {
        return $this->hasMany(PageVersion::class);
    }

    /**
     * Родительская страница
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    /**
     * Дочерние страницы
     */
    public function children(): HasMany
    {
        return $this->hasMany(Page::class, 'parent_id');
    }

    /**
     * Описания различий для создания задач
     */
    public function diffDescriptions(): HasMany
    {
        return $this->hasMany(PageDiffDescription::class);
    }

    /**
     * Создать новую версию страницы
     */
    public function createNewVersion(array $data = []): PageVersion
    {
        $currentVersion = $this->currentVersion;

        if (!$currentVersion) {
            throw new \Exception('Страница не имеет текущей версии');
        }

        $newVersion = $currentVersion->createNewVersion($data);

        // Обновляем version_id в странице
        $this->update(['version_id' => $newVersion->id]);

        return $newVersion;
    }

    /**
     * Получить текущую версию страницы
     */
    public function getCurrentVersion(): ?PageVersion
    {
        return $this->currentVersion;
    }

    /**
     * Получить полную цепочку версий страницы
     */
    public function getVersionChain(): \Illuminate\Database\Eloquent\Collection
    {
        $currentVersion = $this->currentVersion;

        if (!$currentVersion) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return $currentVersion->getVersionChain();
    }

    /**
     * Получить все текущие страницы (без дочерних)
     */
    public static function getCurrentPages(): \Illuminate\Database\Eloquent\Collection
    {
        return static::whereNull('parent_id')
            ->whereNotNull('version_id')
            ->with(['creator', 'children', 'currentVersion'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Получить дерево страниц
     */
    public static function getPageTree(): \Illuminate\Database\Eloquent\Collection
    {
        return static::whereNull('parent_id')
            ->whereNotNull('version_id')
            ->with(['creator', 'children.creator', 'currentVersion'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Получить текущий черновик страницы
     */
    public function getCurrentDraft(): ?PageVersion
    {
        $currentVersion = $this->currentVersion;

        if (!$currentVersion) {
            return null;
        }

        // Если текущая версия сама является черновиком, то активного черновика нет
        if ($currentVersion->is_draft) {
            return null;
        }

        // Находим черновики, которые являются дочерними для текущей версии
        return $this->versions()
            ->where('is_draft', true)
            ->where('previous_version_id', $currentVersion->id)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Проверить, есть ли активный черновик
     */
    public function hasActiveDraft(): bool
    {
        return $this->getCurrentDraft() !== null;
    }

    /**
     * Создать черновик от текущей версии
     */
    public function createDraft(array $data = []): PageVersion
    {
        $currentVersion = $this->currentVersion;

        if (!$currentVersion) {
            throw new \Exception('Страница не имеет текущей версии');
        }

        $draft = $currentVersion->createNewVersion($data);

        // Устанавливаем флаг is_draft = true для нового черновика
        $draft->update(['is_draft' => true]);

        return $draft;
    }

    /**
     * Утвердить черновик
     */
    public function approveDraft(PageVersion $draft): void
    {
        // Обновляем version_id в странице
        $this->update(['version_id' => $draft->id]);
    }

    /**
     * Получить последнюю утвержденную версию
     */
    public function getLatestApprovedVersion(): ?PageVersion
    {
        return $this->currentVersion;
    }

    /**
     * Scope для получения только черновиков
     */
    public function scopeDrafts($query)
    {
        return $query->whereHas('versions', function($q) {
            $q->whereDoesntHave('nextVersion');
        })->whereHas('currentVersion', function($q) {
            $q->whereColumn('page_versions.id', '!=', 'pages.version_id');
        });
    }

    /**
     * Актуализации страницы
     */
    public function actualizations(): HasMany
    {
        return $this->hasMany(Actualization::class);
    }

    /**
     * Последняя актуализация
     */
    public function latestActualization(): HasOne
    {
        return $this->hasOne(Actualization::class)->latestOfMany();
    }

    /**
     * Завершенная актуализация (если есть)
     */
    public function completedActualization(): HasOne
    {
        return $this->hasOne(Actualization::class)
            ->where('status', Actualization::STATUS_COMPLETED)
            ->latestOfMany();
    }

    /**
     * Проверить наличие активной актуализации
     */
    public function hasActiveActualization(): bool
    {
        return $this->actualizations()
            ->whereIn('status', [Actualization::STATUS_PENDING, Actualization::STATUS_PROCESSING])
            ->exists();
    }

    /**
     * Проверить, является ли черновик актуализированным
     * Черновик считается актуализированным, если для него есть завершенная актуализация
     */
    public function isActualized(): bool
    {
        // Только черновики могут быть актуализированными
        $currentVersion = $this->currentVersion;

        if (!$currentVersion) {
            return false;
        }

        // Проверяем, есть ли другие версии после текущей
        $hasNewerVersions = $this->versions()
            ->where('id', '!=', $currentVersion->id)
            ->where('created_at', '>', $currentVersion->created_at)
            ->exists();

        if (!$hasNewerVersions) {
            return false;
        }

        return $this->completedActualization()->exists();
    }

    /**
     * Получить актуализацию для черновика
     */
    public function getActualizationInfo(): ?Actualization
    {
        return $this->completedActualization;
    }

    /**
     * Scope для поиска актуализированных черновиков
     */
    public function scopeActualized($query)
    {
        return $query->whereHas('actualizations', function($q) {
            $q->where('status', Actualization::STATUS_COMPLETED);
        });
    }

    // Методы для обратной совместимости с API

    /**
     * Получить заголовок страницы (из текущей версии)
     */
    public function getTitleAttribute(): ?string
    {
        return $this->currentVersion?->title;
    }

    /**
     * Получить содержимое страницы (из текущей версии)
     */
    public function getContentAttribute(): ?string
    {
        return $this->currentVersion?->content;
    }

    /**
     * Получить файлы страницы (из текущей версии)
     */
    public function getFilesAttribute(): array
    {
        return $this->currentVersion?->files ?? [];
    }

    public function getVersion(int $id): PageVersion
    {
        return PageVersion::where(['page_id' => $this->id, 'id' => $id])->firstOrFail();
    }

    public function checkCurrentVersion(int $version_id): bool
    {
        return $this->currentVersion->id === $version_id;
    }
}
