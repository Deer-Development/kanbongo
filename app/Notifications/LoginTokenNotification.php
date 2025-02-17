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
            ->subject('🚀 Your Kanbongo Login Token')
            ->greeting('Hello ' . $notifiable->first_name . ',')
            ->line('Use the secure login token below to access your account:')
            ->line('**' . $this->token . '**')
            ->line('This token will expire in 10 minutes.')
            ->line('If you did not request this, you can safely ignore this email.')
            ->salutation('See you on Kanbongo! 🚀');
    }
}

