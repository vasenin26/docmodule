<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\GenerationModel;
use App\Services\Pricing\PricingService;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_cost_calculation()
    {
        // Arrange: create a GenerationModel with known prices using factory
        GenerationModel::factory()->priced(0.05, 0.08)->create([
            'name' => 'gpt-test',
            'context_size' => 1000,
        ]);

        $service = new PricingService();

        // 100k prompt, 200k completion -> 0.1*0.05 + 0.2*0.08 = 0.005 + 0.016 = 0.021
        // store as 21 (RUB*1000)
        $cost = $service->calculateCost('gpt-test', 100000, 200000);
        $this->assertEquals(21.0, $cost);
    }
}
