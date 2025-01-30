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
            ->subject('🎉 Welcome to Kanbongo, ' . $notifiable->first_name . '!')
            ->greeting('Hey ' . $notifiable->first_name . ',')
            ->line('We’re thrilled to have you on **Kanbongo**, the most elegant time-tracking and Kanban board platform.')
            ->line('Start organizing your projects seamlessly, track time like a pro, and boost your productivity effortlessly.')
            ->action('🚀 Get Started Now', url('/login'))
            ->salutation('Welcome aboard, and happy tracking! 🚀');
    }
}

