<?php

namespace Tests\Feature;

use App\Models\Techplane;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechplaneDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_download_markdown(): void
    {
        $user = User::factory()->create();
        $techplane = Techplane::factory()->for($user, 'creator')->create([
            'content' => "# Заголовок\nТекст техплана",
        ]);

        $this->actingAs($user);

        $response = $this->get(route('techplanes.download-markdown', ['techplane' => $techplane->id]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="techplane-' . $techplane->id . '.md"');
        $this->assertEquals("# Заголовок\nТекст техплана", $response->getContent());
    }

    public function test_unauthenticated_user_cannot_download(): void
    {
        $user = User::factory()->create();
        $techplane = Techplane::factory()->for($user, 'creator')->create([
            'content' => 'Text',
        ]);

        $response = $this->get(route('techplanes.download-markdown', ['techplane' => $techplane->id]));

        $response->assertRedirect();
    }
}


