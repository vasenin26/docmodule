<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActualizationRequest extends FormRequest
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

        $page = $this->route('page');
        if (!$page) {
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

    /**
     * Получить сообщения об ошибках валидации
     */
    public function messages(): array
    {
        return [
            // Пользовательские сообщения об ошибках
        ];
    }

    /**
     * Подготовить данные для валидации
     */
    protected function prepareForValidation(): void
    {
        // Дополнительная подготовка данных при необходимости
    }

    /**
     * Настроить валидатор
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Проверить, что у страницы нет активной актуализации
            $page = $this->route('page');
            if ($page && $page->hasActiveActualization()) {
                $validator->errors()->add('page', 'У страницы уже есть активная актуализация');
            }

            // Проверить, что у страницы есть прикрепленные файлы
            if ($page && empty($page->files)) {
                $validator->errors()->add('page', 'У страницы должны быть прикрепленные файлы для актуализации');
            }
        });
    }
}
