<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\PageVersion;
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
            'parent_id' => null,
            'version_id' => null, // Будет установлено после создания версии
            'created_by' => User::factory(),
            'deleted_by' => null,
            'deleted_at' => null,
            'project_id' => \App\Models\Project::factory(),
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
            ]);
        });
    }

    /**
     * Страница с версиями
     */
    public function withVersions(): static
    {
        return $this->afterCreating(function (Page $page) {
            // Создаем первую версию
            $firstVersion = PageVersion::factory()->create([
                'page_id' => $page->id,
                'title' => fake()->sentence(3, 6),
                'content' => fake()->paragraphs(3, true),
            ]);
            
            // Устанавливаем первую версию как текущую
            $page->update(['version_id' => $firstVersion->id]);
            
            // Создаем несколько дополнительных версий
            $previousVersion = $firstVersion;
            for ($i = 0; $i < rand(2, 4); $i++) {
                $newVersion = PageVersion::factory()->create([
                    'page_id' => $page->id,
                    'title' => fake()->sentence(3, 6),
                    'content' => fake()->paragraphs(3, true),
                    'previous_version_id' => $previousVersion->id,
                ]);
                $previousVersion = $newVersion;
            }
            
            // Устанавливаем последнюю версию как текущую
            $page->update(['version_id' => $newVersion->id]);
        });
    }

    /**
     * Создать страницу с черновиком
     */
    public function withDraft(): static
    {
        return $this->afterCreating(function (Page $page) {
            // Создаем текущую версию
            $currentVersion = PageVersion::factory()->create([
                'page_id' => $page->id,
                'title' => fake()->sentence(3, 6),
                'content' => fake()->paragraphs(3, true),
            ]);
            
            $page->update(['version_id' => $currentVersion->id]);
            
            // Создаем черновик
            PageVersion::factory()->create([
                'page_id' => $page->id,
                'title' => fake()->sentence(3, 6),
                'content' => fake()->paragraphs(3, true),
                'previous_version_id' => $currentVersion->id,
            ]);
        });
    }

    /**
     * Создать удаленную страницу
     */
    public function deleted(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'deleted_by' => User::factory(),
                'deleted_at' => now(),
            ];
        });
    }
}
