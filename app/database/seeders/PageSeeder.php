<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\PageVersion;
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
            // Создаем страницу
            $page = Page::create([
                'created_by' => $user->id,
                'is_important' => false,
            ]);

            // Создаем первую версию
            $version = PageVersion::create([
                'page_id' => $page->id,
                'title' => $title,
                'content' => $content,
            ]);

            // Устанавливаем первую версию как текущую
            $page->update(['version_id' => $version->id]);

            // Создаем дочерние страницы для некоторых основных страниц
            if (in_array($title, ['Документация API', 'Руководство пользователя'])) {
                $childPages = [
                    'Аутентификация' => 'Методы аутентификации и авторизации',
                    'Обработка ошибок' => 'Стандартные коды ошибок и их обработка',
                    'Примеры использования' => 'Практические примеры интеграции',
                ];

                foreach ($childPages as $childTitle => $childContent) {
                    // Создаем дочернюю страницу
                    $childPage = Page::create([
                        'created_by' => $user->id,
                        'parent_id' => $page->id,
                        'is_important' => false,
                    ]);

                    // Создаем первую версию дочерней страницы
                    $childVersion = PageVersion::create([
                        'page_id' => $childPage->id,
                        'title' => $childTitle,
                        'content' => $childContent,
                    ]);

                    // Устанавливаем первую версию как текущую
                    $childPage->update(['version_id' => $childVersion->id]);
                }
            }
        }

        // Создаем страницу с версиями для демонстрации
        $versionPage = Page::create([
            'created_by' => $user->id,
            'is_important' => false,
        ]);

        // Создаем первую версию
        $firstVersion = PageVersion::create([
            'page_id' => $versionPage->id,
            'title' => 'Страница с версиями',
            'content' => 'Первая версия страницы',
        ]);

        // Устанавливаем первую версию как текущую
        $versionPage->update(['version_id' => $firstVersion->id]);

        // Создаем несколько версий с корректной цепочкой
        $versions = [
            'Вторая версия страницы' => 'Обновленное содержимое второй версии',
            'Третья версия страницы' => 'Финальная версия с улучшениями',
        ];

        $previousVersion = $firstVersion;
        foreach ($versions as $title => $content) {
            $newVersion = PageVersion::create([
                'page_id' => $versionPage->id,
                'title' => $title,
                'content' => $content,
                'previous_version_id' => $previousVersion->id,
            ]);
            $previousVersion = $newVersion;
        }

        // Устанавливаем последнюю версию как текущую
        $versionPage->update(['version_id' => $newVersion->id]);
    }
}
