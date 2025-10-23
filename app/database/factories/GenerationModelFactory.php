<?php

namespace Database\Factories;

use App\Models\GenerationModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GenerationModel>
 */
class GenerationModelFactory extends Factory
{
    protected $model = GenerationModel::class;

    public function definition(): array
    {
        $names = [
            'gpt-4',
            'gpt-4o',
            'gpt-4-turbo',
            'gpt-3.5-turbo',
            'claude-3-opus',
            'claude-3-sonnet',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($names),
            'context_size' => $this->faker->randomElement([8192, 128000, 16385, 200000]),
            // Prices are nullable by default; tests can override explicitly when needed
            'price_in' => null,
            'price_out' => null,
        ];
    }

    /**
     * State with explicit pricing values (per 1_000_000 tokens)
     */
    public function priced(float $priceIn = 0.05, float $priceOut = 0.08): self
    {
        return $this->state(fn(array $attributes) => [
            'price_in' => $priceIn,
            'price_out' => $priceOut,
        ]);
    }
}
