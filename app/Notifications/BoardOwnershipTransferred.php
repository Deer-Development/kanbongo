<?php

namespace App\Notifications;

use App\Models\Container;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Carbon\Carbon;

class BoardOwnershipTransferred extends Notification
{
    use Queueable;

    private Container $container;
    private User $otherUser;
    private string $action;

    public function __construct(Container $container, User $otherUser, string $action)
    {
        $this->container = $container;
        $this->otherUser = $otherUser;
        $this->action = $action;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Board Ownership Change - ' . $this->container->name)
            ->view('emails.board-ownership-transferred', [
                'user' => $notifiable,
                'container' => $this->container,
                'otherUser' => $this->otherUser,
                'action' => $this->action,
                'date' => Carbon::now()->format('F j, Y g:i A')
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'board_ownership_transferred',
            'container_id' => $this->container->id,
            'project_id' => $this->container->project_id,
            'container_name' => $this->container->name,
            'other_user_id' => $this->otherUser->id,
            'other_user_name' => $this->otherUser->full_name,
            'action' => $this->action,
            'date' => Carbon::now()->toISOString()
        ];
    }
} 