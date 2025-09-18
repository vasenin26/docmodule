<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectFile>
 */
class ProjectFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'url' => $this->faker->url(),
            'description' => $this->faker->sentence(10),
        ];
    }

    /**
     * Indicate that the file is an image.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'url' => $this->faker->imageUrl(800, 600, 'business'),
            'description' => $this->faker->sentence(5) . ' (изображение)',
        ]);
    }

    /**
     * Indicate that the file is a PDF document.
     */
    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'url' => 'https://example.com/documents/' . $this->faker->slug(3) . '.pdf',
            'description' => $this->faker->sentence(6) . ' (PDF документ)',
        ]);
    }

    /**
     * Indicate that the file is a code file.
     */
    public function code(): static
    {
        return $this->state(fn (array $attributes) => [
            'url' => 'https://github.com/example/repo/blob/main/src/' . $this->faker->slug(2) . '.php',
            'description' => $this->faker->sentence(4) . ' (исходный код)',
        ]);
    }
}
