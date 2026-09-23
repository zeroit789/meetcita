<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * ES: Proveedor de servicios principal de la app. Hoy no registra nada: los
 *     servicios (AvailabilityService, GoogleCalendarService, TelegramNotifier)
 *     se resuelven solos con el contenedor de Laravel.
 * EN: Main application service provider. It registers nothing today: the
 *     services (AvailabilityService, GoogleCalendarService, TelegramNotifier)
 *     are auto-resolved by Laravel's container.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * ES: Registra servicios de la aplicación en el contenedor.
     * EN: Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * ES: Arranca servicios de la aplicación una vez registrados todos.
     * EN: Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
