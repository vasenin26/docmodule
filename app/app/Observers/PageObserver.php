<?php

namespace App\Observers;

use App\Jobs\CalculateVersionDifferenceJob;
use App\Models\Page;

class PageObserver
{
    /**
     * Handle the Page "created" event.
     */
    public function created(Page $page): void
    {
        // Запускаем Job для создания задачи в трекере при создании новой страницы
        CalculateVersionDifferenceJob::dispatch($page->id, null);
    }

    /**
     * Handle the Page "updated" event.
     */
    public function updated(Page $page): void
    {
        // Для обновления страницы нам нужно найти предыдущую версию
        // Если это новая версия (previous_version_id не null), запускаем Job
        if ($page->previous_version_id) {
            CalculateVersionDifferenceJob::dispatch($page->id, $page->previous_version_id);
        }
    }

    /**
     * Handle the Page "deleted" event.
     */
    public function deleted(Page $page): void
    {
        // Можно добавить логику для обработки удаления страниц
        // Например, создание задачи о том, что страница была удалена
    }

    /**
     * Handle the Page "restored" event.
     */
    public function restored(Page $page): void
    {
        // Можно добавить логику для обработки восстановления страниц
    }

    /**
     * Handle the Page "forceDeleted" event.
     */
    public function forceDeleted(Page $page): void
    {
        // Можно добавить логику для обработки принудительного удаления страниц
    }
}
