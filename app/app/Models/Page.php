<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    protected $fillable = [
        'title',
        'content',
        'created_by',
        'base_id',
        'previous_version_id',
        'parent_id',
        'current',
    ];

    protected $casts = [
        'current' => 'boolean',
    ];

    /**
     * Пользователь, создавший страницу
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Базовая страница (для версионирования)
     */
    public function basePage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'base_id');
    }

    /**
     * Предыдущая версия страницы
     */
    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'previous_version_id');
    }

    /**
     * Следующая версия страницы
     */
    public function nextVersion(): HasMany
    {
        return $this->hasMany(Page::class, 'previous_version_id');
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
     * Все версии страницы (включая текущую)
     */
    public function versions(): HasMany
    {
        return $this->hasMany(Page::class, 'base_id');
    }

    /**
     * Текущая версия страницы
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'base_id')->where('current', true);
    }

    /**
     * Создать новую версию страницы
     */
    public function createNewVersion(array $data = []): Page
    {
        // Определяем base_id для новой версии
        $baseId = $this->base_id ?? $this->id;
        
        // Создаем новую версию
        $newVersion = $this->replicate();
        $newVersion->base_id = $baseId;
        $newVersion->previous_version_id = $this->id;
        $newVersion->current = true;
        $newVersion->fill($data);
        $newVersion->save();

        // Убираем флаг current у всех других версий
        if ($this->base_id) {
            // Если это не первая версия, обновляем все версии с тем же base_id
            Page::where('base_id', $baseId)
                ->where('id', '!=', $newVersion->id)
                ->update(['current' => false]);
        } else {
            // Если это первая версия, обновляем все версии с base_id равным ID этой страницы
            Page::where('base_id', $this->id)
                ->where('id', '!=', $newVersion->id)
                ->update(['current' => false]);
            // Также обновляем саму первую версию
            $this->update(['current' => false]);
        }

        return $newVersion;
    }

    /**
     * Получить текущую версию страницы
     */
    public static function getCurrentVersion(int $baseId): ?Page
    {
        return static::where('base_id', $baseId)
            ->where('current', true)
            ->first();
    }

    /**
     * Получить полную цепочку версий страницы
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
        
        while ($current->nextVersion->count() > 0) {
            $current = $current->nextVersion->first();
            $chain->push($current);
        }

        return $chain;
    }

    /**
     * Получить все текущие страницы (без дочерних)
     */
    public static function getCurrentPages(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('current', true)
            ->whereNull('parent_id')
            ->with(['creator', 'children'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Получить дерево страниц
     */
    public static function getPageTree(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('current', true)
            ->whereNull('parent_id')
            ->with(['creator', 'children.creator'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
