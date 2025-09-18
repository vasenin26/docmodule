<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Solution>
 */
class SolutionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => $this->faker->paragraphs(5, true),
        ];
    }

    /**
     * Indicate that the solution is a code solution.
     */
    public function code(): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => "```php\n" . $this->faker->paragraphs(3, true) . "\n```",
        ]);
    }

    /**
     * Indicate that the solution is a documentation solution.
     */
    public function documentation(): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => $this->faker->paragraphs(8, true),
        ]);
    }

    /**
     * Indicate that the solution is a configuration solution.
     */
    public function configuration(): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => "```yaml\n" . $this->faker->paragraphs(2, true) . "\n```",
        ]);
    }
}
