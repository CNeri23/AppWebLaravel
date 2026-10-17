<?php

namespace App\\Notifications;

use Illuminate\\Bus\\Queueable;
use Illuminate\\Notifications\\Messages\\MailMessage;
use Illuminate\\Notifications\\Notification;
use Illuminate\\Support\\Facades\\Crypt;
use Illuminate\\Support\\Facades\\URL;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutos = (int) config('auth.passwords.users.expire', 60);
        $tokenCifrado = rtrim(strtr(base64_encode(Crypt::encryptString($this->token)), '+/', '-_'), '=');

        $url = URL::temporarySignedRoute(
            'password.reset',
            now()->addMinutes($minutos),
            [
                'token' => $tokenCifrado,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]
        );

        return (new MailMessage)
            ->subject('Restablecimiento de contraseña')
            ->markdown('emails.auth.password-reset', [
                'url' => $url,
                'minutos' => $minutos,
            ]);
    }
}
