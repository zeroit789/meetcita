<?php

namespace App\Providers;

use App\Http\Middleware\AdminPanelPassword;
use App\Support\ModoDemo;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
        // ES: Panel privado: Livewire NO reaplica por defecto los middleware
        //     propios en sus peticiones de actualización (/livewire/update). Con
        //     esto, cada acción de un componente cargado desde /panel vuelve a
        //     pasar por AdminPanelPassword: sin una sesión válida del panel, la
        //     acción no se ejecuta (aunque se reutilice una copia antigua).
        // EN: Private panel: by default Livewire does NOT re-apply custom
        //     middleware on its update requests (/livewire/update). With this,
        //     every action of a component loaded from /panel goes through
        //     AdminPanelPassword again: without a valid panel session the action
        //     doesn't run (even if an old copy of the page is replayed).
        Livewire::addPersistentMiddleware([
            AdminPanelPassword::class,
        ]);

        // ES: Un correo de cita que llega al worker cuando la cita ya cambió
        //     (marca 'citaCambiada' en los datos de su vista) no se envía:
        //     devolver false en MessageSending cancela el envío.
        // EN: A booking email that reaches the worker when the booking has
        //     already changed ('citaCambiada' flag in its view data) isn't
        //     sent: returning false from MessageSending cancels the send.
        Event::listen(MessageSending::class, fn (MessageSending $envio) => ($envio->data['citaCambiada'] ?? false) ? false : null);

        // ES: Demo pública (MEETCITA_DEMO=true): nada sale de verdad.
        // EN: Public demo (MEETCITA_DEMO=true): nothing is really sent.
        ModoDemo::aplicar();
    }
}
