<?php

namespace Database\Seeders;

use App\Models\Advice;
use Illuminate\Database\Seeder;

class AdviceSeeder extends Seeder
{
    public function run(): void
    {
        // Создаёт 5 записей для локальной разработки
        Advice::factory()->count(5)->create();
    }
}
