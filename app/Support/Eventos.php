<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/*
|==============================================================================
| Eventos / Event helper
|==============================================================================
| ES: Dispara un evento de cita (App\Events\*) sin que un listener roto
|     tumbe la operación: la cita ya está guardada, así que un fallo del CRM
|     se registra (solo la clase, sin datos personales) y el flujo sigue.
|     Para trabajo lento, el listener debe implementar ShouldQueue.
| EN: Fires a booking event (App\Events\*) without a broken listener taking
|     the operation down: the booking is already saved, so a CRM failure is
|     logged (class only, no personal data) and the flow goes on. For slow
|     work, the listener should implement ShouldQueue.
|==============================================================================
*/
class Eventos
{
    /**
     * ES: Dispara el evento y registra (sin relanzar) si un listener falla.
     * EN: Fires the event and logs (without rethrowing) if a listener fails.
     */
    public static function disparar(object $evento): void
    {
        try {
            event($evento);
        } catch (\Throwable $e) {
            Log::error('Listener failed for '.$evento::class.' ('.$e::class.').');
        }
    }
}
