<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccessRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $fullName;
    public $organization;
    public $messageText;

    /**
     * Create a new message instance.
     */
    public function __construct(string $fullName, ?string $organization, ?string $messageText)
    {
        $this->fullName = $fullName;
        $this->organization = $organization;
        $this->messageText = $messageText;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this
            ->subject('Новый запрос на доступ')
            ->view('emails.access_request')
            ->with([
                'fullName' => $this->fullName,
                'organization' => $this->organization,
                'messageText' => $this->messageText,
            ]);
    }
}
