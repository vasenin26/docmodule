<?php

namespace Tests\Unit\Models;

use App\Models\Advice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdviceModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_advice_model_can_create_record()
    {
        $advice = Advice::factory()->create();

        $this->assertDatabaseHas('advices', [
            'id' => $advice->id,
        ]);
    }
}
