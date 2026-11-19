<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/*
|==============================================================================
| AppointmentRescheduled — cita cambiada de día u hora / booking rescheduled
|==============================================================================
| ES: El cliente cambió su cita de día u hora con su enlace. La cita vuelve a
|     pendiente (el dueño la confirma de nuevo). Lleva la fecha y la hora
|     anteriores para saber de dónde venía.
|     Pensado para enganchar un CRM u otra herramienta con un listener
|     (ver README, «Eventos»). Se dispara una sola vez por cambio real.
| EN: The client moved their booking to another day or time with their
|     link. The booking goes back to pending (the owner confirms it again).
|     It carries the previous date and time to know where it came from.
|     Meant to hook a CRM or another tool with a listener (see README,
|     "Events"). Fired once per real change.
|==============================================================================
*/
class AppointmentRescheduled
{
    use Dispatchable, SerializesModels;

    /**
     * ES: $appointment = la cita con la fecha nueva; $previousDate ("YYYY-MM-DD")
     *     y $previousTime ("HH:MM") = las de antes.
     * EN: $appointment = the booking with its new date; $previousDate
     *     ("YYYY-MM-DD") and $previousTime ("HH:MM") = the old ones.
     */
    public function __construct(
        public Appointment $appointment,
        public string $previousDate,
        public string $previousTime,
    ) {}
}
