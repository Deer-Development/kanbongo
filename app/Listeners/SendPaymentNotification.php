<?php

namespace App\Listeners;

use App\Events\PaymentProcessed;
use App\Notifications\PaymentProcessedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPaymentNotification implements ShouldQueue
{
    public function handle(PaymentProcessed $event): void
    {
        $payment = $event->payment;
        $user = $payment->user;

        $user->notify(new PaymentProcessedNotification($payment));
    }
}