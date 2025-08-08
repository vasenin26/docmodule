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
            // Создаем несколько версий
            for ($i = 0; $i < rand(2, 4); $i++) {
                $page->createNewVersion([
                    'title' => fake()->sentence(3, 6),
                    'content' => fake()->paragraphs(3, true),
                ]);
            }
        });
    }
}
