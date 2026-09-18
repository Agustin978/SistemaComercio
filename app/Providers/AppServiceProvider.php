<?php

namespace App\Providers;

use App\Catalog\Models\Category;
use App\Catalog\Models\Product;
use App\Catalog\Policies\CategoryPolicy;
use App\Catalog\Policies\ProductPolicy;
use App\Inventory\Models\StockMovement;
use App\Inventory\Policies\StockMovementPolicy;
use App\Reporting\Contracts\ReportTransmitter;
use App\Reporting\Transmitters\LocalReportTransmitter;
use Illuminate\Support\Facades\Gate;
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
        // Registro explícito: el autodescubrimiento asume App\Models y App\Policies, no el layout modular.
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(StockMovement::class, StockMovementPolicy::class);
    }
}
