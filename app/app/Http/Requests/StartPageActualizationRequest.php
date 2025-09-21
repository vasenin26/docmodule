<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartPageActualizationRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения этого запроса
     */
    public function authorize(): bool
    {
        // Проверяем, что пользователь аутентифицирован
        if (!$this->user()) {
            return false;
        }

        // Простая проверка: пользователь может актуализировать страницу если он ее создатель
        // или если это общедоступная функция (в зависимости от бизнес-логики)
        return true; // Временно разрешаем всем аутентифицированным пользователям
    }

    /**
     * Получить правила валидации для запроса
     */
    public function rules(): array
    {
        return [
            // Дополнительные правила валидации могут быть добавлены при необходимости
        ];
    }
}
