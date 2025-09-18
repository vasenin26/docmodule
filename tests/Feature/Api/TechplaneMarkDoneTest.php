<?php

namespace Tests\Feature\Api;

use App\Models\Techplane;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechplaneMarkDoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_done_success(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $techplane = Techplane::factory()->create([
            'status' => Techplane::EXECUTION_PLANNED,
            'generation_status' => Techplane::STATUS_COMPLETED,
        ]);

        $response = $this->postJson("/api/techplanes/{$techplane->id}/done", [
            'mergeRequestUrl' => 'https://git.example.com/group/project/merge_requests/123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', Techplane::EXECUTION_EXECUTED)
            ->assertJsonPath('solutions.0.mergeRequests.0.url', 'https://git.example.com/group/project/merge_requests/123');

        $this->assertDatabaseHas('solution_merge_requests', [
            'url' => 'https://git.example.com/group/project/merge_requests/123',
        ]);
    }

    public function test_mark_done_validation_error(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $techplane = Techplane::factory()->create([
            'status' => Techplane::EXECUTION_PLANNED,
            'generation_status' => Techplane::STATUS_COMPLETED,
        ]);

        $response = $this->postJson("/api/techplanes/{$techplane->id}/done", [
            'mergeRequestUrl' => 'not-a-url',
        ]);

        $response->assertStatus(422);
    }

    public function test_mark_done_conflict_when_generation_not_completed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $techplane = Techplane::factory()->create([
            'status' => Techplane::EXECUTION_PLANNED,
            'generation_status' => Techplane::STATUS_GENERATING,
        ]);

        $response = $this->postJson("/api/techplanes/{$techplane->id}/done", [
            'mergeRequestUrl' => 'https://git.example.com/mr/1',
        ]);

        $response->assertStatus(409);
    }

    public function test_mark_done_not_found(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/techplanes/999999/done', [
            'mergeRequestUrl' => 'https://git.example.com/mr/1',
        ]);

        $response->assertStatus(404);
    }
}
