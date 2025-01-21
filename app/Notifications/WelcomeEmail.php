<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class WelcomeEmail extends Notification
{
    use Queueable;

    public function __construct() {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Welcome to Our Platform!')
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('Thank you for joining us. We’re thrilled to have you on board.')
            ->action('Get Started', url('/login'))
            ->line('If you have any questions, feel free to reach out!');
    }
}

