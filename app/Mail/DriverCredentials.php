<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DriverCredentials extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;

    public function __construct($user, $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('app.name') . ' - Your Driver Login Credentials',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.drivers.credentials',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
