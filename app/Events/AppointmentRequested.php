<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Foundation\Events\Dispatchable;

/*
|==============================================================================
| AppointmentRequested — cita nueva pedida / new booking requested
|==============================================================================
| ES: Se acaba de crear una cita: desde la página pública (source = "web",
|     queda pendiente) o dada de alta a mano en el panel (source = "panel",
|     y justo después llega también AppointmentConfirmed).
|     Pensado para enganchar un CRM u otra herramienta con un listener
|     (ver README, «Eventos»). Se dispara una sola vez por cambio real.
|     $appointment es una foto fija sin datos de contacto (AppointmentSnapshot).
| EN: A booking was just created: from the public page (source = "web",
|     it stays pending) or added by hand in the panel (source = "panel",
|     and AppointmentConfirmed follows right after).
|     Meant to hook a CRM or another tool with a listener (see README,
|     "Events"). Fired once per real change.
|     $appointment is a fixed snapshot without contact data (AppointmentSnapshot).
|==============================================================================
*/
class AppointmentRequested
{
    use Dispatchable;

    // ES: Origen de la cita. EN: Where the booking came from.
    public const SOURCE_WEB = 'web';

    public const SOURCE_PANEL = 'panel';

    // ES: Foto fija de la cita en el momento del evento, sin datos de
    //     contacto (ver AppointmentSnapshot; el contacto, con ->current()).
    // EN: Fixed picture of the booking at the moment of the event, without
    //     contact data (see AppointmentSnapshot; the contact, via ->current()).
    public readonly AppointmentSnapshot $appointment;

    /**
     * ES: $appointment = la cita creada; $source = "web" o "panel".
     * EN: $appointment = the created booking; $source = "web" or "panel".
     */
    public function __construct(
        Appointment $appointment,
        public readonly string $source = self::SOURCE_WEB,
    ) {
        $this->appointment = AppointmentSnapshot::from($appointment);
    }
}
