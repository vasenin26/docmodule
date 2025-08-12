<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем тестовые проекты для существующих пользователей
        $users = \App\Models\User::all();
        
        if ($users->isEmpty()) {
            $this->command->warn('Нет пользователей в базе данных. Сначала запустите DatabaseSeeder.');
            return;
        }

        foreach ($users as $user) {
            \App\Models\Project::factory(rand(2, 5))
                ->for($user, 'owner')
                ->create();
        }

        $this->command->info('Созданы тестовые проекты для пользователей.');
    }
}
