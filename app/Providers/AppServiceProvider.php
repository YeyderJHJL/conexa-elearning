<?php

namespace App\Providers;

use Filament\Pages\BasePage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Los botones de guardar/cancelar de los formularios del admin quedan fijos al pie al hacer scroll.
        BasePage::stickyFormActions();
    }
}
