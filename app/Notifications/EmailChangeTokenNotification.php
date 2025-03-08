<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailChangeTokenNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token,
        private readonly string $newEmail
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Email Change Request')
            ->line('You have requested to change your email address to: ' . $this->newEmail)
            ->line('Your email change token is: ' . $this->token)
            ->line('If you did not request this change, no further action is required.');
    }
} 