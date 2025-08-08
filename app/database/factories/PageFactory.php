<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3, 6),
            'content' => fake()->paragraphs(3, true),
            'created_by' => User::factory(),
            'base_id' => null, // Для первой версии base_id = null
            'previous_version_id' => null, // Для первой версии previous_version_id = null
            'current' => true,
        ];
    }

    /**
     * Страница с дочерними страницами
     */
    public function withChildren(): static
    {
        return $this->afterCreating(function (Page $page) {
            Page::factory(rand(2, 5))->create([
                'parent_id' => $page->id,
                'current' => true,
            ]);
        });
    }

    /**
     * Страница с версиями
     */
    public function withVersions(): static
    {
        return $this->afterCreating(function (Page $page) {
            // Создаем несколько версий с корректной цепочкой
            $previousVersion = $page;
            for ($i = 0; $i < rand(2, 4); $i++) {
                $newVersion = $previousVersion->createNewVersion([
                    'title' => fake()->sentence(3, 6),
                    'content' => fake()->paragraphs(3, true),
                ]);
                $previousVersion = $newVersion;
            }
        });
    }

    /**
     * Создать страницу как новую версию существующей
     */
    public function asVersion(Page $basePage): static
    {
        return $this->state(function (array $attributes) use ($basePage) {
            return [
                'base_id' => $basePage->base_id ?? $basePage->id,
                'previous_version_id' => $basePage->id,
                'current' => true,
            ];
        });
    }
}
