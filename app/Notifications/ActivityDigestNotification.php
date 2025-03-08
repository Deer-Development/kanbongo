<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Carbon\Carbon;

class ActivityDigestNotification extends Notification
{
    use Queueable;

    private array $digestData;

    public function __construct(array $digestData)
    {
        $this->digestData = $digestData;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Recent Activity Update - ' . Carbon::now()->format('F j, Y g:i A'))
            ->view('emails.activity-digest', [
                'user' => $notifiable,
                'digestData' => $this->digestData
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'activity_digest',
            'data' => $this->digestData
        ];
    }
} 