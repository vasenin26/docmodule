<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @param Page $page
 */
class PageVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'title',
        'content',
        'previous_version_id',
        'is_draft',
    ];

    protected function casts(): array
    {
        return [
            'is_draft' => 'boolean',
        ];
    }

    /**
     * Связанные файлы проекта через пивот
     */
    public function projectFiles(): BelongsToMany
    {
        return $this->belongsToMany(ProjectFile::class, 'project_file_page_version');
    }

    /**
     * Страница, к которой принадлежит версия
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Предыдущая версия
     */
    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(PageVersion::class, 'previous_version_id');
    }

    /**
     * Следующая версия
     */
    public function nextVersion(): HasOne
    {
        return $this->hasOne(PageVersion::class, 'previous_version_id');
    }

    /**
     * Связь с задачами
     */
    public function versionDiffTasks(): HasMany
    {
        return $this->hasMany(VersionDiffTask::class, 'page_version_id');
    }

    /**
     * Для обратной совместимости
     */
    public function diffDescriptions(): HasMany
    {
        return $this->versionDiffTasks();
    }

    /**
     * Получить полную цепочку версий
     */
    public function getVersionChain(): \Illuminate\Database\Eloquent\Collection
    {
        // Находим первую версию в цепочке
        $firstVersion = $this;
        while ($firstVersion->previous_version_id) {
            $firstVersion = $firstVersion->previousVersion;
        }

        // Собираем всю цепочку версий
        $chain = new \Illuminate\Database\Eloquent\Collection([$firstVersion]);
        $current = $firstVersion;

        while ($current->nextVersion) {
            $current = $current->nextVersion;
            $chain->push($current);
        }

        return $chain;
    }

    /**
     * Создать новую версию
     */
    public function createNewVersion(array $data = []): PageVersion
    {
        $newVersion = $this->replicate();

        $newVersion->fill($data);
        $newVersion->previous_version_id = $this->id;
        $newVersion->is_draft = true;

        $newVersion->save();

        return $newVersion;
    }

    /**
     * Проверить, есть ли активная актуализация для этой версии
     * Ищем актуализации, привязанные непосредственно к этой версии
     */
    public function hasActiveActualization(): bool
    {
        return Actualization::where('page_version_id', $this->id)
            ->whereIn('status', [Actualization::STATUS_PENDING, Actualization::STATUS_PROCESSING])
            ->exists();
    }

    /**
     * Получить активную актуализацию для этой версии
     */
    public function getActiveActualization(): ?Actualization
    {
        return Actualization::where('page_version_id', $this->id)
            ->whereIn('status', [Actualization::STATUS_PENDING, Actualization::STATUS_PROCESSING])
            ->with(['pageVersion', 'createdBy'])
            ->first();
    }

    /**
     * Получить завершенную актуализацию для этой версии
     */
    public function getCompletedActualization(): ?Actualization
    {
        return Actualization::where('page_version_id', $this->id)
            ->where('status', Actualization::STATUS_COMPLETED)
            ->with(['pageVersion', 'llmChat'])
            ->first();
    }

    /**
     * Sync project files to this version using provided attachment inputs.
     * Each input item must contain 'url' and optionally 'description'.
     */
    public function syncProjectFilesByUrls(array $attachmentsInput, int $projectId): void
    {
        if (empty($attachmentsInput)) {
            $this->projectFiles()->sync([]);
            return;
        }

        $now = now();
        $rows = array_map(static function (array $a) use ($projectId, $now) {
            return [
                'project_id' => $projectId,
                'url' => $a['url'],
                'description' => $a['description'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $attachmentsInput);

        if ($rows) {
            ProjectFile::upsert($rows, ['project_id', 'url'], ['description', 'updated_at']);
        }

        $urls = array_map(static fn($a) => $a['url'], $attachmentsInput);
        $ids = ProjectFile::query()
            ->where('project_id', $projectId)
            ->when($urls, static fn($q) => $q->whereIn('url', $urls))
            ->pluck('id')
            ->all();

        $this->projectFiles()->sync($ids);
    }

    /**
     * Copy project file links from another version to this one.
     */
    public function copyProjectFilesFrom(PageVersion $source): void
    {
        $ids = $source->projectFiles()->pluck('project_files.id')->all();
        $this->projectFiles()->sync($ids);
    }

    /**
     * Backward-compatible accessor: вернуть массив URL прикрепленных файлов
     */
    public function getFilesAttribute(): array
    {
        // Возвращаем список URL из связанной коллекции projectFiles
        return $this->projectFiles()->pluck('url')->all();
    }
}
