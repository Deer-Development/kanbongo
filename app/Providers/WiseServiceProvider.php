<?php

namespace App\Providers;

use App\Services\Wise\WisePaymentService;
use Illuminate\Support\ServiceProvider;

class WiseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WisePaymentService::class, function ($app) {
            return new WisePaymentService(
                config('services.wise.api_key'),
                config('services.wise.profile_id'),
                config('services.wise.api_url')
            );
        });
    }
}