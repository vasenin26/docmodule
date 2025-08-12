<?php

namespace App\Observers;

use App\Models\Page;

class PageObserver
{
    /**
     * Handle the Page "created" event.
     */
    public function created(Page $page): void
    {
        // Логика создания задач перенесена в контроллер
    }

    /**
     * Handle the Page "updated" event.
     */
    public function updated(Page $page): void
    {
        // Удаляем автоматическое создание задач
        // Observer используется только для аудита и служебных целей
    }

    /**
     * Handle the Page "saved" event.
     */
    public function saved(Page $page): void
    {
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
