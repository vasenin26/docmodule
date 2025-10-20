<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Prices from docs/models.md in USD per 1M tokens
        // price_in comes from "Input", price_out comes from "Output"
        // Convert to RUB with multiplier 120 and set uniform context_size 200000

        $models = [
            ['name' => 'gpt-5', 'input_usd' => 1.25, 'output_usd' => 10.00],
            ['name' => 'gpt-5-mini', 'input_usd' => 0.25, 'output_usd' => 2.00],
            ['name' => 'gpt-5-nano', 'input_usd' => 0.05, 'output_usd' => 0.40],
            ['name' => 'gpt-5-codex', 'input_usd' => 1.25, 'output_usd' => 10.00],
            ['name' => 'gpt-5-pro', 'input_usd' => 15.00, 'output_usd' => 120.00],
            ['name' => 'gpt-4.1', 'input_usd' => 2.00, 'output_usd' => 8.00],
            ['name' => 'gpt-4.1-mini', 'input_usd' => 0.40, 'output_usd' => 1.60],
            ['name' => 'gpt-4.1-nano', 'input_usd' => 0.10, 'output_usd' => 0.40],
            ['name' => 'gpt-4o', 'input_usd' => 2.50, 'output_usd' => 10.00],
            ['name' => 'gpt-4o-2024-05-13', 'input_usd' => 5.00, 'output_usd' => 15.00],
            ['name' => 'gpt-4o-mini', 'input_usd' => 0.15, 'output_usd' => 0.60],
            ['name' => 'gpt-realtime', 'input_usd' => 4.00, 'output_usd' => 16.00],
            ['name' => 'gpt-realtime-mini', 'input_usd' => 0.60, 'output_usd' => 2.40],
            ['name' => 'gpt-4o-realtime-preview', 'input_usd' => 5.00, 'output_usd' => 20.00],
            ['name' => 'gpt-4o-mini-realtime-preview', 'input_usd' => 0.60, 'output_usd' => 2.40],
            ['name' => 'o1', 'input_usd' => 15.00, 'output_usd' => 60.00],
            ['name' => 'o1-pro', 'input_usd' => 150.00, 'output_usd' => 600.00],
            ['name' => 'o3-pro', 'input_usd' => 20.00, 'output_usd' => 80.00],
            ['name' => 'o3', 'input_usd' => 2.00, 'output_usd' => 8.00],
            ['name' => 'o3-deep-research', 'input_usd' => 10.00, 'output_usd' => 40.00],
            ['name' => 'o4-mini', 'input_usd' => 1.10, 'output_usd' => 4.40],
            ['name' => 'o4-mini-deep-research', 'input_usd' => 2.00, 'output_usd' => 8.00],
            ['name' => 'o3-mini', 'input_usd' => 1.10, 'output_usd' => 4.40],
            ['name' => 'o1-mini', 'input_usd' => 1.10, 'output_usd' => 4.40],
            ['name' => 'codex-mini-latest', 'input_usd' => 1.50, 'output_usd' => 6.00],
            ['name' => 'gpt-5-search-api', 'input_usd' => 1.25, 'output_usd' => 10.00],
            ['name' => 'gpt-4o-mini-search-preview', 'input_usd' => 0.15, 'output_usd' => 0.60],
            ['name' => 'gpt-4o-search-preview', 'input_usd' => 2.50, 'output_usd' => 10.00],
        ];

        foreach ($models as $model) {
            $priceInRub = $model['input_usd'] !== null ? round($model['input_usd'] * 120, 2) : null;
            $priceOutRub = $model['output_usd'] !== null ? round($model['output_usd'] * 120, 2) : null;

            DB::table('generation_models')->updateOrInsert(
                ['name' => $model['name']],
                [
                    'context_size' => 200000,
                    'price_in' => $priceInRub,
                    'price_out' => $priceOutRub,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // Intentionally left blank to avoid deleting or altering data
    }
};
