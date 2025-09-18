<?php

namespace Database\Factories;

use App\Common\Enums\GenerationStatus;
use App\Models\Implementation;
use App\Models\Techplane;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Implementation>
 */
class ImplementationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => $this->faker->paragraphs(3, true),
            'techplane_id' => \App\Models\Techplane::factory(),
            'chat_id' => null,
            'status' => GenerationStatus::PENDING,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the implementation is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GenerationStatus::COMPLETED,
            'content' => $this->faker->paragraphs(5, true),
        ]);
    }
}
