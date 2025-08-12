<?php

namespace App\Observers;

use App\Jobs\CalculateVersionDifferenceJob;
use App\Jobs\GenerateTaskDescriptionJob;
use App\Models\Page;
use App\Models\PageDiffDescription;

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
        // Проверяем, было ли изменение статуса с черновика на утвержденную версию
        if ($page->wasChanged('current') && $page->current && $page->isDirty('current')) {
            $this->handlePageApproval($page);
        }
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

    /**
     * Обработка утверждения страницы
     */
    private function handlePageApproval(Page $page): void
    {
        // Создаем запись PageDiffDescription для утвержденной страницы
        $diffDescription = PageDiffDescription::create([
            'page_id' => $page->id,
            'content' => '', // Будет заполнено job'ом
            'created_by' => auth()->id() ?? $page->created_by, // Используем текущего пользователя или создателя страницы
        ]);

        // Запускаем цепочку job'ов с ID созданной записи
        GenerateTaskDescriptionJob::dispatch($diffDescription->id);
    }
}
