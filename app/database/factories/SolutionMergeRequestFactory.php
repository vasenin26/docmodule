<?php

namespace Database\Factories;

use App\Models\Solution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SolutionMergeRequest>
 */
class SolutionMergeRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'solution_id' => Solution::factory(),
            'url' => $this->faker->url(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the merge request is from GitHub.
     */
    public function github(): static
    {
        return $this->state(fn (array $attributes) => [
            'url' => 'https://github.com/' . $this->faker->userName() . '/' . $this->faker->slug(2) . '/pull/' . $this->faker->numberBetween(1, 1000),
        ]);
    }

    /**
     * Indicate that the merge request is from GitLab.
     */
    public function gitlab(): static
    {
        return $this->state(fn (array $attributes) => [
            'url' => 'https://gitlab.com/' . $this->faker->userName() . '/' . $this->faker->slug(2) . '/-/merge_requests/' . $this->faker->numberBetween(1, 1000),
        ]);
    }

    /**
     * Indicate that the merge request is from Bitbucket.
     */
    public function bitbucket(): static
    {
        return $this->state(fn (array $attributes) => [
            'url' => 'https://bitbucket.org/' . $this->faker->userName() . '/' . $this->faker->slug(2) . '/pull-requests/' . $this->faker->numberBetween(1, 1000),
        ]);
    }
}
