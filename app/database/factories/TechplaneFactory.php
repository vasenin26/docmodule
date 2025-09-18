<?php

namespace Database\Factories;

use App\Common\Enums\GenerationStatus;
use App\Models\Techplane;
use App\Models\User;
use App\Models\VersionDiffTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Techplane>
 */
class TechplaneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => VersionDiffTask::factory(),
            'content' => $this->faker->paragraphs(3, true),
            'created_by' => User::factory(),
            'chat_id' => null,
            'generation_status' => Techplane::STATUS_PENDING,
            'status' => Techplane::EXECUTION_PLANNED,
        ];
    }

    /**
     * Indicate that the techplane is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'generation_status' => Techplane::STATUS_COMPLETED,
            'content' => $this->faker->paragraphs(5, true),
        ]);
    }

    /**
     * Indicate that the techplane is generating.
     */
    public function generating(): static
    {
        return $this->state(fn (array $attributes) => [
            'generation_status' => Techplane::STATUS_GENERATING,
        ]);
    }

    /**
     * Indicate that the techplane is executed.
     */
    public function executed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Techplane::EXECUTION_EXECUTED,
        ]);
    }
}
