<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactSubmittedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public ContactMessage $contactMessage)
    {
        $this->onQueue('mail');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New contact form message: '.$this->contactMessage->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-submitted',
        );
    }
}
