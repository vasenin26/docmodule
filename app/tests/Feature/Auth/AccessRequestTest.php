<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use App\Mail\AccessRequestMail;
use App\Models\User;

class AccessRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_request_page_shown_when_request_only_enabled()
    {
        putenv('REGISTRATION_REQUEST_ONLY=true');

        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertSee('Request access');
    }

    public function test_submitting_access_request_sends_mail_and_does_not_create_user()
    {
        Mail::fake();

        // Ensure env variable is enabled for this request
        putenv('REGISTRATION_REQUEST_ONLY=true');
        putenv('REGISTRATION_REQUEST_ADMIN_EMAIL=admin@example.com');

        $this->assertEquals(0, User::count());

        $response = $this->post('/register', [
            'full_name' => 'Иван Иванов',
            'contact' => 'ivan@example.com',
            'organization' => 'Acme Ltd',
            'message' => 'Прошу доступ',
        ]);

        // Mail was sent
        Mail::assertSent(AccessRequestMail::class, function ($mail) {
            return $mail->fullName === 'Иван Иванов'
                && $mail->contact === 'ivan@example.com'
                && $mail->organization === 'Acme Ltd'
                && $mail->messageText === 'Прошу доступ';
        });

        // No user created
        $this->assertEquals(0, User::count());

        // Redirect or flash
        $response->assertSessionHas('status');
    }
}
