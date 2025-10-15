<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $models = [
            'llm-studio' => [
                'name' => 'llm-studio',
                'context' => 25000,
            ],
            'summary' => [
                'name' => 'gpt-4.1',
                'context' => 1047576,
            ],
            'gpt-5' => [
                'name' => 'gpt-5',
                'context' => 400000,
            ],
            'gpt-5-mini' => [
                'name' => 'gpt-5-mini',
                'context' => 400000,
            ],
            'gpt-5-nano' => [
                'name' => 'gpt-5-nano',
                'context' => 400000,
            ],
            'gpt-4.1' => [
                'name' => 'gpt-4.1',
                'context' => 1047576,
            ],
        ];

        foreach ($models as $key => $data) {
            DB::table('generation_models')->updateOrInsert(
                ['name' => $data['name']],
                [
                    'context_size' => $data['context'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        $names = [
            'llm-studio',
            'gpt-4.1',
            'gpt-5',
            'gpt-5-mini',
            'gpt-5-nano',
        ];

        DB::table('generation_models')->whereIn('name', $names)->delete();
    }
};


