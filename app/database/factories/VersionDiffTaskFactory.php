<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VersionDiffTask>
 */
class VersionDiffTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => \App\Models\Project::factory(),
            'page_version_id' => \App\Models\PageVersion::factory(),
            'content' => $this->faker->paragraphs(3, true),
            'created_by' => \App\Models\User::factory(),
            'generation_status' => $this->faker->randomElement([
                \App\Models\VersionDiffTask::STATUS_PENDING,
                \App\Models\VersionDiffTask::STATUS_GENERATING,
                \App\Models\VersionDiffTask::STATUS_COMPLETED,
                \App\Models\VersionDiffTask::STATUS_FAILED,
            ]),
            'llm_chat_id' => null,
            'edited_at' => null,
        ];
    }
}
