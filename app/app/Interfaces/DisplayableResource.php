<?php

namespace App\Interfaces;

interface DisplayableResource
{
    /**
     * Возвращает маршрут для отображения ресурса
     */
    public function viewPage(): string;
}
