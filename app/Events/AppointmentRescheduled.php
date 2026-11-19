<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Foundation\Events\Dispatchable;

/*
|==============================================================================
| AppointmentRescheduled — cita cambiada de día u hora / booking rescheduled
|==============================================================================
| ES: El cliente cambió su cita de día u hora con su enlace. La cita vuelve a
|     pendiente (el dueño la confirma de nuevo). Lleva la fecha y la hora
|     anteriores para saber de dónde venía.
|     Pensado para enganchar un CRM u otra herramienta con un listener
|     (ver README, «Eventos»). Se dispara una sola vez por cambio real.
|     $appointment es una foto fija sin datos de contacto (AppointmentSnapshot).
| EN: The client moved their booking to another day or time with their
|     link. The booking goes back to pending (the owner confirms it again).
|     It carries the previous date and time to know where it came from.
|     Meant to hook a CRM or another tool with a listener (see README,
|     "Events"). Fired once per real change.
|     $appointment is a fixed snapshot without contact data (AppointmentSnapshot).
|==============================================================================
*/
class AppointmentRescheduled
{
    use Dispatchable;

    // ES: Foto fija de la cita en el momento del evento, sin datos de
    //     contacto (ver AppointmentSnapshot; el contacto, con ->current()).
    // EN: Fixed picture of the booking at the moment of the event, without
    //     contact data (see AppointmentSnapshot; the contact, via ->current()).
    public readonly AppointmentSnapshot $appointment;

    /**
     * ES: $appointment = la cita con la fecha nueva; $previousDate ("YYYY-MM-DD")
     *     y $previousTime ("HH:MM") = las de antes.
     * EN: $appointment = the booking with its new date; $previousDate
     *     ("YYYY-MM-DD") and $previousTime ("HH:MM") = the old ones.
     */
    public function __construct(
        Appointment $appointment,
        public readonly string $previousDate,
        public readonly string $previousTime,
    ) {
        $this->appointment = AppointmentSnapshot::from($appointment);
    }
}
