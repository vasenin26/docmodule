<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'files',
        'is_draft',
    ];

    protected $casts = [
        'files' => 'array',
    ];

    /**
     * Получить список файлов версии
     */
    public function getFilesAttribute($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Валидировать ссылки на файлы в репозиториях
     */
    public function validateFilePaths(array $files): bool
    {
        foreach ($files as $file) {
            // Файл должен быть строкой с корректным URL
            if (!is_string($file)) {
                return false;
            }

            // URL должен быть корректной ссылкой
            if (!filter_var($file, FILTER_VALIDATE_URL)) {
                return false;
            }

            // Дополнительная проверка, что это ссылка на файл в git репозитории
            if (!$this->isGitRepositoryFileUrl($file)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Проверить, является ли URL ссылкой на файл в git репозитории
     */
    private function isGitRepositoryFileUrl(string $url): bool
    {
        // Проверяем популярные git хостинги
        $gitHosts = ['github.com', 'gitlab.com', 'bitbucket.org'];

        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['host'])) {
            return false;
        }

        foreach ($gitHosts as $host) {
            if (str_contains($parsedUrl['host'], $host)) {
                return true;
            }
        }

        return false;
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

        // Обеспечиваем корректное копирование поля files
        if (!isset($data['files']) && $this->files) {
            $newVersion->files = $this->files;
        }

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
}
