<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegisterTokenNotification extends Notification
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
            ->subject('🚀 Your Kanbongo Verification Token')
            ->greeting('Welcome to Kanbongo, ' . $notifiable->first_name . '!')
            ->line('We’re excited to have you on **Kanbongo**, the ultimate time-tracking and Kanban board experience.')
            ->line('Use the secure login token below to verify and access your account:')
            ->line('🔑 **' . $this->token . '**')
            ->line('This token is valid for **10 minutes**.')
            ->line('If you did not request this, you can safely ignore this email.')
            ->salutation('See you on Kanbongo! 🚀');
    }
}

