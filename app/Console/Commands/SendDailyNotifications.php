<?php

namespace App\Console\Commands;

use App\Services\DailyNotificationService;
use Illuminate\Console\Command;

class SendDailyNotifications extends Command
{
    protected $signature = 'notifications:daily';
    protected $description = 'Send daily activity notifications to users';

    public function handle(DailyNotificationService $notificationService): int
    {
        $this->info('Sending daily notifications...');
        $notificationService->sendDailyNotifications();
        $this->info('Daily notifications sent successfully!');
        return 0;
    }
} 