<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\PageVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PageVersion>
 */
class PageVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'page_id' => Page::factory(),
            'title' => $this->faker->sentence(3),
            'content' => $this->faker->paragraphs(3, true),
            'previous_version_id' => null,
            'files' => [],
        ];
    }

    /**
     * Создать версию с предыдущей версией
     */
    public function withPreviousVersion(PageVersion $previousVersion): static
    {
        return $this->state(fn (array $attributes) => [
            'page_id' => $previousVersion->page_id,
            'previous_version_id' => $previousVersion->id,
        ]);
    }

    /**
     * Создать версию с файлами
     */
    public function withFiles(array $files): static
    {
        return $this->state(fn (array $attributes) => [
            'files' => $files,
        ]);
    }
}
