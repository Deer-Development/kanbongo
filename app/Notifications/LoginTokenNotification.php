<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginTokenNotification extends Notification
{
    protected string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Login Token')
            ->greeting('Hello ' . $notifiable->first_name . ',')
            ->line('Use the token below to log in to your account:')
            ->line('**' . $this->token . '**')
            ->line('This token will expire in 10 minutes.')
            ->line('If you did not request this login, please ignore this email.');
    }
}

