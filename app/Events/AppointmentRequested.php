<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/*
|==============================================================================
| AppointmentRequested — cita nueva pedida / new booking requested
|==============================================================================
| ES: Se acaba de crear una cita: desde la página pública (source = "web",
|     queda pendiente) o dada de alta a mano en el panel (source = "panel",
|     y justo después llega también AppointmentConfirmed).
|     Pensado para enganchar un CRM u otra herramienta con un listener
|     (ver README, «Eventos»). Se dispara una sola vez por cambio real.
| EN: A booking was just created: from the public page (source = "web",
|     it stays pending) or added by hand in the panel (source = "panel",
|     and AppointmentConfirmed follows right after).
|     Meant to hook a CRM or another tool with a listener (see README,
|     "Events"). Fired once per real change.
|==============================================================================
*/
class AppointmentRequested
{
    use Dispatchable, SerializesModels;

    // ES: Origen de la cita. EN: Where the booking came from.
    public const SOURCE_WEB = 'web';

    public const SOURCE_PANEL = 'panel';

    /**
     * ES: $appointment = la cita creada; $source = "web" o "panel".
     * EN: $appointment = the created booking; $source = "web" or "panel".
     */
    public function __construct(
        public Appointment $appointment,
        public string $source = self::SOURCE_WEB,
    ) {}
}
