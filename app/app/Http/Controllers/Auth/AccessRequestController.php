<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\AccessRequestMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class AccessRequestController extends Controller
{
    public function __construct()
    {
        // Apply throttle at route level (see routes). Optionally enable here.
    }

    /**
     * Show access request form (optional, RegisteredUserController::create handles view switching).
     */
    public function create(): Response
    {
        return Inertia::render('auth/AccessRequest');
    }

    /**
     * Handle incoming access request form.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'contact' => 'required|string|max:255',
            'organization' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:2000',
        ]);

        $adminEmails = env('REGISTRATION_REQUEST_ADMIN_EMAIL');

        if (empty($adminEmails)) {
            // If no admin email configured, log and return 500-like response
            Log::error('Access request: REGISTRATION_REQUEST_ADMIN_EMAIL is not configured.');
            return back()->withErrors(['_system' => 'Сервер не настроен для обработки запросов. Свяжитесь с администратором.']);
        }

        // Support comma-separated list of emails
        $recipients = array_filter(array_map('trim', explode(',', $adminEmails)));

        try {
            foreach ($recipients as $email) {
                Mail::to($email)->send(new AccessRequestMail(
                    $validated['full_name'],
                    $validated['organization'] ?? null,
                    $validated['message'] ?? null,
                    $validated['contact']
                ));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send access request email: '.$e->getMessage());
            return back()->withErrors(['_system' => 'Не удалось отправить запрос. Попробуйте позже.']);
        }

        return redirect()->route('login')->with('status', 'Ваш запрос отправлен. Мы свяжемся с вами.');
    }
}
