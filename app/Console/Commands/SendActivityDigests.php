<?php

namespace App\Console\Commands;

use App\Services\ActivityDigestService;
use Illuminate\Console\Command;

class SendActivityDigests extends Command
{
    protected $signature = 'notifications:activity-digest';
    protected $description = 'Send activity digest notifications to users';

    public function handle(ActivityDigestService $digestService): int
    {
        $this->info('Sending activity digests...');
        $digestService->sendActivityDigests();
        $this->info('Activity digests sent successfully!');
        return 0;
    }
} 