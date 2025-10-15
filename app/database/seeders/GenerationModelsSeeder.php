<?php

namespace Database\Seeders;

use App\Models\GenerationModel;
use Illuminate\Database\Seeder;

class GenerationModelsSeeder extends Seeder
{
    public function run(): void
    {
        $models = [
            ['name' => 'gpt-4', 'context_size' => 8192],
            ['name' => 'gpt-4o', 'context_size' => 128000],
            ['name' => 'gpt-4-turbo', 'context_size' => 128000],
            ['name' => 'gpt-3.5-turbo', 'context_size' => 16385],
            ['name' => 'claude-3-opus', 'context_size' => 200000],
            ['name' => 'claude-3-sonnet', 'context_size' => 200000],
            ['name' => 'claude-3-haiku', 'context_size' => 200000],
        ];

        foreach ($models as $m) {
            GenerationModel::firstOrCreate(['name' => $m['name']], ['context_size' => $m['context_size']]);
        }
    }
}


