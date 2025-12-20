<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Ticket;
use App\Observers\TicketObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Register Ticket Observer for automatic activity logging
        Ticket::observe(TicketObserver::class);
    }
}
