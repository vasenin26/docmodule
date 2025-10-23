<?php

namespace App\Services\Pricing;

use App\Models\GenerationModel;

class PricingService
{
    /**
     * Calculate the cost for a task based on prompt/completion tokens and GenerationModel pricing.
     * Returns the cost to store (in RUB * 1000) as float, or null if pricing is unavailable.
     *
     * @param string|null $agentModelName
     * @param int $promptTokens
     * @param int $completionTokens
     * @return float|null
     */
    public function calculateCost(?string $agentModelName, int $promptTokens, int $completionTokens): ?float
    {
        if (empty($agentModelName)) {
            return null;
        }

        // Загрузить pricing по имени модели
        $model = GenerationModel::where('name', $agentModelName)->first();
        if (!$model) {
            return null;
        }

        // Цена за 1 000 000 токенов
        if (is_null($model->price_in) || is_null($model->price_out)) {
            return null;
        }

        // Расчет по формуле: (prompt_tokens / 1_000_000) * price_in + (completion_tokens / 1_000_000) * price_out
        $costRaw = ($promptTokens / 1000000.0) * (float)$model->price_in
            + ($completionTokens / 1000000.0) * (float)$model->price_out;

        // Храним как RUB * 1000 для точности
        $costToStore = (float) round($costRaw * 1000);

        // Вернуть значение, которое можно напрямую записать в поле cost
        return $costToStore;
    }
}
