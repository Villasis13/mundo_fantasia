<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecuperarContrasena extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $resetUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Recuperación de contraseña');
    }

    public function build(): static
    {
        return $this
            ->from(env('MAIL_USERNAME'), config('app.name'))
            ->subject('Recuperación de contraseña')
            ->view('emails.recuperar-contrasena');
    }
}
