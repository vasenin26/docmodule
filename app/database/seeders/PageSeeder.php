<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем пользователя если его нет
        $user = User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Admin',
            'password' => bcrypt('password'),
        ]);

        // Создаем основные страницы
        $mainPages = [
            'Документация API' => 'Полное описание API для разработчиков',
            'Руководство пользователя' => 'Пошаговое руководство по использованию системы',
            'Часто задаваемые вопросы' => 'Ответы на популярные вопросы пользователей',
            'Установка и настройка' => 'Инструкции по установке и первоначальной настройке',
        ];

        foreach ($mainPages as $title => $content) {
            $page = Page::create([
                'title' => $title,
                'content' => $content,
                'created_by' => $user->id,
                'current' => true,
            ]);

            // Создаем дочерние страницы для некоторых основных страниц
            if (in_array($title, ['Документация API', 'Руководство пользователя'])) {
                $childPages = [
                    'Аутентификация' => 'Методы аутентификации и авторизации',
                    'Обработка ошибок' => 'Стандартные коды ошибок и их обработка',
                    'Примеры использования' => 'Практические примеры интеграции',
                ];

                foreach ($childPages as $childTitle => $childContent) {
                    Page::create([
                        'title' => $childTitle,
                        'content' => $childContent,
                        'created_by' => $user->id,
                        'parent_id' => $page->id,
                        'current' => true,
                    ]);
                }
            }
        }

        // Создаем несколько страниц с версиями для демонстрации
        $versionPage = Page::create([
            'title' => 'Страница с версиями',
            'content' => 'Первая версия страницы',
            'created_by' => $user->id,
            'current' => false,
        ]);

        // Создаем несколько версий
        $versions = [
            'Вторая версия страницы' => 'Обновленное содержимое второй версии',
            'Третья версия страницы' => 'Финальная версия с улучшениями',
        ];

        foreach ($versions as $title => $content) {
            $versionPage->createNewVersion([
                'title' => $title,
                'content' => $content,
            ]);
        }
    }
}
