<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Repository>
 */
class RepositoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'url' => $this->faker->url(),
            'options' => [
                'branch' => $this->faker->randomElement(['main', 'develop', 'feature/new-feature']),
                'path' => $this->faker->randomElement(['/', '/docs', '/src']),
            ],
        ];
    }

    /**
     * Indicate that the repository is GitHub.
     */
    public function github(): static
    {
        return $this->state(fn (array $attributes) => [
            'url' => 'https://github.com/' . $this->faker->userName() . '/' . $this->faker->slug(2),
            'options' => [
                'branch' => 'main',
                'path' => '/',
                'type' => 'github',
            ],
        ]);
    }

    /**
     * Indicate that the repository is GitLab.
     */
    public function gitlab(): static
    {
        return $this->state(fn (array $attributes) => [
            'url' => 'https://gitlab.com/' . $this->faker->userName() . '/' . $this->faker->slug(2),
            'options' => [
                'branch' => 'main',
                'path' => '/',
                'type' => 'gitlab',
            ],
        ]);
    }
}
