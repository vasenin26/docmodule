<?php

namespace Database\Factories;

use App\Models\Actualization;
use App\Models\LLMChat;
use App\Models\PageVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Actualization>
 */
class ActualizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'page_id' => null, // Будет установлено автоматически через page_version_id
            'page_version_id' => PageVersion::factory(),
            'status' => Actualization::STATUS_PENDING,
            'llm_chat_id' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the actualization is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Actualization::STATUS_PROCESSING,
            'llm_chat_id' => LLMChat::factory(),
        ]);
    }

    /**
     * Indicate that the actualization is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Actualization::STATUS_COMPLETED,
            'llm_chat_id' => LLMChat::factory(),
        ]);
    }

    /**
     * Indicate that the actualization failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Actualization::STATUS_FAILED,
            'llm_chat_id' => LLMChat::factory(),
        ]);
    }

    /**
     * Create actualization for a specific draft version.
     */
    public function forDraft(PageVersion $pageVersion): static
    {
        return $this->state(fn (array $attributes) => [
            'page_id' => $pageVersion->page_id,
            'page_version_id' => $pageVersion->id,
        ]);
    }
}
