<?php

namespace App\Services\Pricing;

use App\Models\GenerationModel;

class PricingService
{
    /**
     * Calculate the cost for a task based on prompt/completion tokens and GenerationModel pricing.
     * Returns the cost to store (in RUB * 1000) as int, or null if pricing is unavailable.
     *
     * @param string|null $agentModelName
     * @param int $promptTokens
     * @param int $completionTokens
     * @return int|null
     */
    public function calculateCost(?string $agentModelName, int $promptTokens, int $completionTokens): ?int
    {
        if (empty($agentModelName)) {
            return null;
        }

        // Load pricing by model name
        $model = GenerationModel::where('name', $agentModelName)->first();
        if (!$model) {
            return null;
        }

        // Price per 1_000_000 tokens
        if (is_null($model->price_in) || is_null($model->price_out)) {
            return null;
        }

        // Calculation: (prompt_tokens / 1_000_000) * price_in + (completion_tokens / 1_000_000) * price_out
        $costRaw = ($promptTokens / 1000000.0) * (float)$model->price_in
            + ($completionTokens / 1000000.0) * (float)$model->price_out;

        // Store as integer RUB * 1000 for precision
        $costToStore = (int) round($costRaw * 1000);

        return $costToStore;
    }
}
