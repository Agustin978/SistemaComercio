<?php

namespace App\Providers;

use App\Reporting\Contracts\ReportTransmitter;
use App\Reporting\Transmitters\LocalReportTransmitter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ReportTransmitter::class, LocalReportTransmitter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
