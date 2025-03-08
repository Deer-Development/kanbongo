<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Carbon\Carbon;

class DailyMemberActivityNotification extends Notification
{
    use Queueable;

    private array $activityData;

    public function __construct(array $activityData)
    {
        $this->activityData = $activityData;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Daily Activity Summary - ' . Carbon::now()->format('F j, Y'))
            ->view('emails.daily-member-summary', [
                'user' => $notifiable,
                'activityData' => $this->activityData
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'daily_member_activity',
            'title' => 'Daily Activity Summary',
            'data' => $this->activityData
        ];
    }
} 