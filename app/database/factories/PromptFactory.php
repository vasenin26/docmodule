<?php

namespace Database\Factories;

use App\Common\Enums\PromptType;
use App\Models\Prompt;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Prompt>
 */
class PromptFactory extends Factory
{
    protected $model = Prompt::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'type' => $this->faker->randomElement(PromptType::cases())->value,
            'content' => $this->faker->paragraphs(3, true),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the prompt is for task manager.
     */
    public function taskManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PromptType::TASK_MANAGER->value,
        ]);
    }

    /**
     * Indicate that the prompt is for task description.
     */
    public function taskDescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PromptType::TASK_DESCRIPTION->value,
        ]);
    }
}
